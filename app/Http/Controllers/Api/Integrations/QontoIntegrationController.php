<?php

namespace App\Http\Controllers\Api\Integrations;

use App\Http\Controllers\Controller;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Customer\Customer;
use App\Models\Integrations\QontoClientMapping;
use App\Models\Integrations\QontoConnection;
use App\Models\Integrations\QontoSyncReview;
use App\Models\Integrations\PdpInvoiceSubmission;
use App\Models\Workflow\Invoices;
use App\Services\Integrations\QontoClientSyncService;
use App\Services\Integrations\QontoConnectionService;
use App\Services\Integrations\Pdp\PdpInvoiceService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class QontoIntegrationController extends Controller
{
    /**
     * Scopes Qonto requis par l'intégration :
     * - organization.read  → IBAN du compte, porté par payment_methods.iban
     * - client.*           → synchronisation des tiers
     * - client_invoices.read / client_invoice.write → dépôt et suivi des factures
     *   (le pluriel sur la lecture n'est pas une faute : c'est le nom du scope Qonto)
     */
    public const OAUTH_SCOPES = 'offline_access organization.read client.read client.write client_invoices.read client_invoice.write';

    public function __construct(
        private QontoClientSyncService  $syncService,
        private QontoConnectionService  $connectionService,
        private PdpInvoiceService       $pdpInvoiceService,
    ) {}


    public function connect(Request $request): JsonResponse
    {
        $tenantId = auth('api')->id();
        $state = Str::random(40);

        Cache::put("qonto.oauth.state.{$state}", $tenantId, now()->addMinutes(10));

        $query = http_build_query([
            'client_id' => config('services.qonto.client_id'),
            'redirect_uri' => route('api.integrations.qonto.callback', absolute: true),
            'response_type' => 'code',
            'scope' => self::OAUTH_SCOPES,
            'state' => $state,
        ]);

        return response()->json([
            'authorization_url' => rtrim(config('services.qonto.oauth_base_url', 'https://oauth.qonto.com'), '/').'/oauth2/auth?'.$query,
            'state' => $state,
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $tenantId = auth('api')->id();
        $connection = QontoConnection::where('tenant_id', $tenantId)->first();

        $clientId = (string) config('services.qonto.client_id', '');
        $clientSecret = (string) config('services.qonto.client_secret', '');
        $featureEnabled = $clientId !== '' && $clientSecret !== '';

        return response()->json([
            'feature_enabled' => $featureEnabled,
            'connected' => $connection !== null,
            'last_sync_at' => $connection?->last_sync_at?->toISOString(),
            'import_bidirectionnel' => (bool) ($connection?->import_bidirectionnel ?? false),
            'scope' => $connection?->scope,
        ]);
    }

    public function callback(Request $request)
    {
        $code = $request->query('code');
        $state = $request->query('state');

        abort_if(! $code || ! $state, 422, 'Missing OAuth parameters.');

        $tenantId = Cache::pull("qonto.oauth.state.{$state}");
        abort_if(! $tenantId, 422, 'Invalid or expired OAuth state.');

        $tenantId = (int) $tenantId;

        $tokenResponse = Http::asForm()->post(rtrim(config('services.qonto.oauth_base_url', 'https://oauth.qonto.com'), '/').'/oauth2/token', [
            'grant_type' => 'authorization_code',
            'client_id' => config('services.qonto.client_id'),
            'client_secret' => config('services.qonto.client_secret'),
            'redirect_uri' => route('api.integrations.qonto.callback', absolute: true),
            'code' => $code,
        ])->throw()->json();

        QontoConnection::updateOrCreate(
            ['tenant_id' => $tenantId],
            [
                'access_token' => Crypt::encryptString($tokenResponse['access_token']),
                'refresh_token' => Crypt::encryptString($tokenResponse['refresh_token']),
                'access_token_expires_at' => now()->addSeconds((int) ($tokenResponse['expires_in'] ?? 3600)),
                'scope' => $tokenResponse['scope'] ?? null,
            ]
        );

        return redirect()->route('admin.integrations.qonto')->with('success', 'Connexion Qonto établie.');
    }

    public function sync(Request $request): JsonResponse
    {
        $tenantId = auth('api')->id();
        $connection = $this->getValidConnection($tenantId);
        $importBidirectional = $request->has('import_bidirectionnel')
            ? (bool) $request->boolean('import_bidirectionnel')
            : (bool) $connection->import_bidirectionnel;
        $qontoClients = $this->fetchQontoClients($connection);
        $wemClients = $this->fetchWemClients($tenantId);

        $result = $this->syncService->reconcile($tenantId, $wemClients, $qontoClients, $importBidirectional);

        $createdInQonto = 0;
        foreach ($wemClients as $wemClient) {
            $existingMapping = QontoClientMapping::where('tenant_id', $tenantId)
                ->where('wem_client_id', $wemClient['id'])
                ->first();

            if ($existingMapping && $existingMapping->qonto_client_id) {
                continue;
            }

            $skipStatuses = ['review_required', 'imported_from_qonto', 'created_in_qonto'];
            if ($existingMapping && in_array($existingMapping->sync_status, $skipStatuses, true)) {
                continue;
            }

            try {
                $createdClient = $this->createQontoClient($connection, $wemClient);

                QontoClientMapping::updateOrCreate(
                    ['tenant_id' => $tenantId, 'wem_client_id' => $wemClient['id']],
                    [
                        'qonto_client_id' => (string) ($createdClient['id'] ?? null),
                        'sync_status' => 'created_in_qonto',
                        'matching_score' => 100,
                        'last_sync_at' => now(),
                    ]
                );

                $createdInQonto++;
            } catch (\Throwable $e) {
                Log::warning('QontoSync: failed to create client in Qonto', [
                    'tenant_id' => $tenantId,
                    'wem_client_id' => $wemClient['id'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $connection->forceFill(['last_sync_at' => now()])->save();

        return response()->json($result + [
            'created_in_qonto_count' => $createdInQonto,
            'reviews_count' => QontoSyncReview::where('tenant_id', $tenantId)->where('status', 'pending')->count(),
        ]);
    }

    public function reconcile(Request $request): JsonResponse
    {
        $tenantId = auth('api')->id();
        $connection = $this->getValidConnection($tenantId);

        $result = $this->syncService->reconcile(
            $tenantId,
            $this->fetchWemClients($tenantId),
            $this->fetchQontoClients($connection),
            $request->boolean('import_bidirectionnel', false)
        );

        return response()->json($result);
    }

    public function push(Request $request, int $wemClientId): JsonResponse
    {
        $tenantId = auth('api')->id();
        $connection = $this->getValidConnection($tenantId);
        $wemClient = collect($this->fetchWemClients($tenantId))->firstWhere('id', $wemClientId);

        abort_if(! $wemClient, 404, 'WEM client not found.');

        $createdClient = $this->createQontoClient($connection, $wemClient);

        $mapping = QontoClientMapping::updateOrCreate(
            ['tenant_id' => $tenantId, 'wem_client_id' => $wemClientId],
            [
                'qonto_client_id' => (string) ($createdClient['id'] ?? null),
                'sync_status' => 'created_in_qonto',
                'matching_score' => 100,
                'last_sync_at' => now(),
            ]
        );

        return response()->json(['mapping' => $mapping]);
    }

    public function resolve(Request $request, int $reviewId): JsonResponse
    {
        $tenantId = auth('api')->id();
        $validated = $request->validate([
            'action' => ['required', 'in:link,ignore'],
            'qonto_client_id' => ['nullable', 'string'],
        ]);

        $review = QontoSyncReview::where('tenant_id', $tenantId)->findOrFail($reviewId);

        if ($validated['action'] === 'link') {
            abort_if(empty($validated['qonto_client_id']) && empty($review->qonto_client_id), 422, 'qonto_client_id is required.');

            QontoClientMapping::updateOrCreate(
                ['tenant_id' => $tenantId, 'wem_client_id' => $review->wem_client_id],
                [
                    'qonto_client_id' => $validated['qonto_client_id'] ?? $review->qonto_client_id,
                    'sync_status' => 'linked',
                    'matching_score' => $review->matching_score,
                    'last_sync_at' => now(),
                ]
            );

            $review->status = 'resolved';
        } else {
            $review->status = 'ignored';
        }

        $review->resolved_at = now();
        $review->resolved_by = auth('api')->id();
        $review->save();

        return response()->json(['review' => $review]);
    }


    public function settings(Request $request): JsonResponse
    {
        $tenantId = auth('api')->id();
        $validated = $request->validate([
            'import_bidirectionnel' => ['required', 'boolean'],
        ]);

        $connection = QontoConnection::where('tenant_id', $tenantId)->firstOrFail();
        $connection->import_bidirectionnel = (bool) $validated['import_bidirectionnel'];
        $connection->save();

        return response()->json([
            'import_bidirectionnel' => $connection->import_bidirectionnel,
        ]);
    }

    public function disconnect(Request $request): JsonResponse
    {
        $tenantId = auth('api')->id();

        QontoConnection::where('tenant_id', $tenantId)->delete();

        return response()->json(['disconnected' => true]);
    }

    public function submitInvoice(Request $request, int $invoiceId): JsonResponse
    {
        $tenantId = auth('api')->id();
        $invoice  = Invoices::where('user_id', $tenantId)->findOrFail($invoiceId);

        abort_if(
            $invoice->invoice_type !== 1,
            422,
            'Seules les factures (type 1) peuvent être soumises à Qonto.'
        );

        try {
            $mapping = $this->pdpInvoiceService->submit($invoice);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Aucune connexion Qonto active : connectez le compte avant de déposer une facture.',
            ], 422);
        } catch (\RuntimeException $e) {
            // Données manquantes ou refus Qonto : le message est destiné à l'utilisateur.
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['mapping' => $mapping]);
    }

    public function pollInvoice(Request $request, int $invoiceId): JsonResponse
    {
        $tenantId = auth('api')->id();
        $mapping  = PdpInvoiceSubmission::where('tenant_id', $tenantId)
            ->where('invoice_id', $invoiceId)
            ->firstOrFail();

        $mapping = $this->pdpInvoiceService->poll($mapping);

        return response()->json(['mapping' => $mapping]);
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
                ->throw()
                ->json();

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
            ])
            ->throw()
            ->json();

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
            /** @var Companies|null $company */
            $company = $contact->companie;
            /** @var CompaniesAddresses|null $address */
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
