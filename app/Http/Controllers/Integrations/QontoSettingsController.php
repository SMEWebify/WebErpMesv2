<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Models\Customer\Customer;
use App\Services\Integrations\QontoConnectionService;
use App\Models\Integrations\QontoClientMapping;
use App\Models\Integrations\QontoConnection;
use App\Models\Integrations\QontoSyncReview;
use App\Services\Integrations\QontoClientSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class QontoSettingsController extends Controller
{
    public function __construct(
        private QontoClientSyncService $syncService,
        private QontoConnectionService $connectionService,
    ) {}

    public function index(Request $request)
    {
        $tenantId = $request->user()->id;
        $connection = QontoConnection::where('tenant_id', $tenantId)->first();

        $pendingReviews = QontoSyncReview::where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get();

        $wemClientNames = [];
        if ($pendingReviews->isNotEmpty()) {
            $wemClientIds = $pendingReviews->pluck('wem_client_id')->unique();
            $wemClientNames = Customer::whereIn('id', $wemClientIds)
                ->with('companie:id,label')
                ->get()
                ->mapWithKeys(fn ($c) => [$c->id => $c->companie?->label ?? $c->name])
                ->all();
        }

        $stats = [
            'linked'              => QontoClientMapping::where('tenant_id', $tenantId)->where('sync_status', 'linked')->count(),
            'created_in_qonto'    => QontoClientMapping::where('tenant_id', $tenantId)->where('sync_status', 'created_in_qonto')->count(),
            'review_required'     => QontoClientMapping::where('tenant_id', $tenantId)->where('sync_status', 'review_required')->count(),
            'imported_from_qonto' => QontoClientMapping::where('tenant_id', $tenantId)->where('sync_status', 'imported_from_qonto')->count(),
        ];

        $featureEnabled = (string) config('services.qonto.client_id', '') !== ''
            && (string) config('services.qonto.client_secret', '') !== '';

        return view('integrations.qonto-settings', compact(
            'connection', 'pendingReviews', 'wemClientNames', 'stats', 'featureEnabled'
        ));
    }

    public function connect(Request $request)
    {
        $tenantId = $request->user()->id;
        $state = Str::random(40);

        Cache::put("qonto.oauth.state.{$state}", $tenantId, now()->addMinutes(10));

        $query = http_build_query([
            'client_id'     => config('services.qonto.client_id'),
            'redirect_uri'  => route('admin.integrations.qonto.callback', absolute: true),
            'response_type' => 'code',
            'scope'         => 'offline_access client.read client.write',
            'state'         => $state,
        ]);

        return redirect(
            rtrim(config('services.qonto.oauth_base_url', 'https://oauth.qonto.com'), '/').'/oauth2/auth?'.$query
        );
    }

    public function callback(Request $request)
    {
        $code  = $request->query('code');
        $state = $request->query('state');

        abort_if(! $code || ! $state, 422, 'Paramètres OAuth manquants.');

        $tenantId = Cache::pull("qonto.oauth.state.{$state}");
        abort_if(! $tenantId, 422, 'État OAuth invalide ou expiré.');

        $tokenResponse = Http::asForm()->post(
            rtrim(config('services.qonto.oauth_base_url', 'https://oauth.qonto.com'), '/').'/oauth2/token',
            [
                'grant_type'    => 'authorization_code',
                'client_id'     => config('services.qonto.client_id'),
                'client_secret' => config('services.qonto.client_secret'),
                'redirect_uri'  => route('admin.integrations.qonto.callback', absolute: true),
                'code'          => $code,
            ]
        )->throw()->json();

        QontoConnection::updateOrCreate(
            ['tenant_id' => (int) $tenantId],
            [
                'access_token'            => Crypt::encryptString($tokenResponse['access_token']),
                'refresh_token'           => Crypt::encryptString($tokenResponse['refresh_token']),
                'access_token_expires_at' => now()->addSeconds((int) ($tokenResponse['expires_in'] ?? 3600)),
                'scope'                   => $tokenResponse['scope'] ?? null,
            ]
        );

        return redirect()->route('admin.integrations.qonto')
            ->with('success', 'Connexion Qonto établie avec succès.');
    }

    public function sync(Request $request)
    {
        $tenantId  = $request->user()->id;
        $connection = $this->getValidConnection($tenantId);
        $qontoClients = $this->fetchQontoClients($connection);
        $wemClients   = $this->fetchWemClients($tenantId);

        $result = $this->syncService->reconcile(
            $tenantId, $wemClients, $qontoClients, (bool) $connection->import_bidirectionnel
        );

        $createdInQonto = 0;
        foreach ($wemClients as $wemClient) {
            $mapping = QontoClientMapping::where('tenant_id', $tenantId)
                ->where('wem_client_id', $wemClient['id'])
                ->first();

            if ($mapping && $mapping->qonto_client_id) {
                continue;
            }

            $skipStatuses = ['review_required', 'imported_from_qonto', 'created_in_qonto'];
            if ($mapping && in_array($mapping->sync_status, $skipStatuses, true)) {
                continue;
            }

            try {
                $created = $this->createQontoClient($connection, $wemClient);
                QontoClientMapping::updateOrCreate(
                    ['tenant_id' => $tenantId, 'wem_client_id' => $wemClient['id']],
                    [
                        'qonto_client_id' => (string) ($created['id'] ?? null),
                        'sync_status'     => 'created_in_qonto',
                        'matching_score'  => 100,
                        'last_sync_at'    => now(),
                    ]
                );
                $createdInQonto++;
            } catch (\Throwable $e) {
                Log::warning('QontoSync: failed to create client in Qonto', [
                    'tenant_id'     => $tenantId,
                    'wem_client_id' => $wemClient['id'],
                    'error'         => $e->getMessage(),
                ]);
            }
        }

        $connection->forceFill(['last_sync_at' => now()])->save();

        $msg = "{$result['mapped_wem_count']} liés";
        if ($createdInQonto)                     $msg .= ", {$createdInQonto} créés dans Qonto";
        if ($result['created_in_wem_count'] > 0) $msg .= ", {$result['created_in_wem_count']} importés depuis Qonto";
        $reviews = QontoSyncReview::where('tenant_id', $tenantId)->where('status', 'pending')->count();
        if ($reviews)                            $msg .= ", {$reviews} à réviser";

        return redirect()->route('admin.integrations.qonto')
            ->with('success', "Synchronisation terminée : {$msg}.");
    }

    public function settings(Request $request)
    {
        $validated  = $request->validate(['import_bidirectionnel' => ['required', 'boolean']]);
        $connection = QontoConnection::where('tenant_id', $request->user()->id)->firstOrFail();
        $connection->import_bidirectionnel = (bool) $validated['import_bidirectionnel'];
        $connection->save();

        return redirect()->route('admin.integrations.qonto')
            ->with('success', 'Paramètres sauvegardés.');
    }

    public function disconnect(Request $request)
    {
        QontoConnection::where('tenant_id', $request->user()->id)->delete();

        return redirect()->route('admin.integrations.qonto')
            ->with('success', 'Déconnecté de Qonto.');
    }

    public function resolve(Request $request, int $reviewId)
    {
        $tenantId  = $request->user()->id;
        $validated = $request->validate([
            'action'          => ['required', 'in:link,ignore'],
            'qonto_client_id' => ['nullable', 'string'],
        ]);

        $review = QontoSyncReview::where('tenant_id', $tenantId)->findOrFail($reviewId);

        if ($validated['action'] === 'link') {
            abort_if(empty($validated['qonto_client_id']) && empty($review->qonto_client_id), 422, 'qonto_client_id requis.');

            QontoClientMapping::updateOrCreate(
                ['tenant_id' => $tenantId, 'wem_client_id' => $review->wem_client_id],
                [
                    'qonto_client_id' => $validated['qonto_client_id'] ?? $review->qonto_client_id,
                    'sync_status'     => 'linked',
                    'matching_score'  => $review->matching_score,
                    'last_sync_at'    => now(),
                ]
            );
            $review->status = 'resolved';
        } else {
            $review->status = 'ignored';
        }

        $review->resolved_at  = now();
        $review->resolved_by  = $tenantId;
        $review->save();

        return redirect()->route('admin.integrations.qonto')
            ->with('success', 'Revue traitée.');
    }

    private function getValidConnection(int $tenantId): QontoConnection
    {
        return $this->connectionService->getValidConnection($tenantId);
    }

    private function fetchQontoClients(QontoConnection $connection): array
    {
        $token   = Crypt::decryptString($connection->access_token);
        $baseUrl = rtrim(config('services.qonto.api_base_url', 'https://thirdparty.qonto.com/v2'), '/');
        $clients = [];
        $nextPage = 1;

        do {
            $response = Http::withToken($token)
                ->get("{$baseUrl}/clients", ['page' => $nextPage, 'per_page' => 100])
                ->throw()->json();

            $clients  = array_merge($clients, $response['clients'] ?? $response['data'] ?? []);
            $nextPage = $response['meta']['next_page'] ?? null;
        } while ($nextPage !== null);

        return $clients;
    }

    private function createQontoClient(QontoConnection $connection, array $wemClient): array
    {
        $response = Http::withToken(Crypt::decryptString($connection->access_token))
            ->post(rtrim(config('services.qonto.api_base_url', 'https://thirdparty.qonto.com/v2'), '/').'/clients', [
                'name'                => $wemClient['name'],
                'email'               => $wemClient['email'] ?? null,
                'registration_number' => $wemClient['siren'] ?? null,  // Qonto v2 : registration_number
                'vat_number'          => $wemClient['vat_number'] ?? null,
                'postal_code'         => $wemClient['postal_code'] ?? null,
                'city'                => $wemClient['city'] ?? null,
            ])->throw()->json();

        return $response['client'] ?? $response['data'] ?? $response;
    }

    private function fetchWemClients(int $tenantId): array
    {
        // unique('companies_id') : une entrée par entreprise même si plusieurs contacts
        $contacts = Customer::query()
            ->whereHas('companie', fn ($q) => $q->where('user_id', $tenantId)->where('statu_customer', 1))
            ->with(['companie', 'companie.Addresses' => fn ($q) => $q->where('default', 1)->limit(1)])
            ->get()
            ->unique('companies_id');

        return $contacts->map(function (Customer $contact) {
            $company = $contact->companie;
            $address = $company?->Addresses?->first();

            return [
                'id'          => $company->id,
                'name'        => $company->label,
                'email'       => $contact->mail,
                'siren'       => $company->siren,
                'siret'       => null,
                'vat_number'  => $company->intra_community_vat,
                'postal_code' => $address?->zipcode,
                'city'        => $address?->city,
            ];
        })->values()->all();
    }
}
