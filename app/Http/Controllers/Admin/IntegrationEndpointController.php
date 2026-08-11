<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Integrations\IntegrationEndpointRequest;
use App\Jobs\SyncAllSheetLotsToN2P;
use App\Models\Integrations\IntegrationDelivery;
use App\Models\Integrations\IntegrationEndpoint;
use App\Services\N2P\N2PClient;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class IntegrationEndpointController extends Controller
{
    private const SECRET_PLACEHOLDER = '__keep__';

    public function index(): View
    {
        $endpoints = IntegrationEndpoint::query()
            ->orderBy('system_code')
            ->orderBy('direction')
            ->get();

        return view('integrations.endpoints.index', [
            'endpoints' => $endpoints,
        ]);
    }

    public function create(): View
    {
        $endpoint = new IntegrationEndpoint([
            'auth_method'           => IntegrationEndpoint::AUTH_HMAC,
            'hmac_header'           => 'X-Signature-256',
            'timestamp_header'      => 'X-Timestamp',
            'timestamp_tolerance_s' => 300,
            'verify_ssl'            => true,
            'is_active'             => false,
            'retry_max'             => 5,
            'retry_backoff'         => IntegrationEndpoint::DEFAULT_RETRY_BACKOFF,
            'events'                => [],
        ]);

        return view('integrations.endpoints.edit', [
            'endpoint' => $endpoint,
            'isNew'    => true,
        ]);
    }

    public function store(IntegrationEndpointRequest $request): RedirectResponse
    {
        $attributes = $this->prepareAttributes($request, null);

        [$attributes, $revealed] = $this->autoGenerateMissingSecrets($attributes);

        $endpoint = IntegrationEndpoint::create($attributes);

        return redirect()
            ->route('admin.integrations.endpoints.edit', $endpoint)
            ->with('success', __('general_content.success_trans_key'))
            ->with('revealed_secrets', $revealed);
    }

    public function edit(IntegrationEndpoint $endpoint): View
    {
        return view('integrations.endpoints.edit', [
            'endpoint' => $endpoint,
            'isNew'    => false,
        ]);
    }

    public function update(IntegrationEndpointRequest $request, IntegrationEndpoint $endpoint): RedirectResponse
    {
        $attributes = $this->prepareAttributes($request, $endpoint);

        $endpoint->fill($attributes)->save();

        return redirect()
            ->route('admin.integrations.endpoints.edit', $endpoint)
            ->with('success', __('general_content.success_trans_key'));
    }

    public function destroy(IntegrationEndpoint $endpoint): RedirectResponse
    {
        $endpoint->delete();

        return redirect()
            ->route('admin.integrations.endpoints.index')
            ->with('success', __('general_content.success_trans_key'));
    }

    public function regenerate(IntegrationEndpoint $endpoint): RedirectResponse
    {
        $endpoint->regenerateSecrets();
        $endpoint->save();

        $revealed = array_filter([
            'bearer_token' => $endpoint->requiresBearer() ? $endpoint->bearer_token : null,
            'hmac_secret'  => $endpoint->requiresHmac()   ? $endpoint->hmac_secret : null,
        ]);

        return redirect()
            ->route('admin.integrations.endpoints.edit', $endpoint)
            ->with('success', 'Secrets régénérés — copie-les MAINTENANT (invisibles ensuite).')
            ->with('revealed_secrets', $revealed);
    }

    /**
     * Génère automatiquement bearer/hmac s'ils sont attendus mais vides.
     * Retourne [$attributes, $revealedSecrets] pour flash session vers l'UI.
     */
    private function autoGenerateMissingSecrets(array $attributes): array
    {
        $revealed = [];
        $auth = $attributes['auth_method'] ?? IntegrationEndpoint::AUTH_NONE;

        $needsBearer = in_array($auth, [IntegrationEndpoint::AUTH_BEARER, IntegrationEndpoint::AUTH_BEARER_HMAC], true);
        if ($needsBearer && empty($attributes['bearer_token'])) {
            $attributes['bearer_token'] = \Illuminate\Support\Str::random(64);
            $revealed['bearer_token'] = $attributes['bearer_token'];
        }

        $needsHmac = in_array($auth, [IntegrationEndpoint::AUTH_HMAC, IntegrationEndpoint::AUTH_BEARER_HMAC], true);
        if ($needsHmac && empty($attributes['hmac_secret'])) {
            $attributes['hmac_secret'] = \Illuminate\Support\Str::random(64);
            $revealed['hmac_secret'] = $attributes['hmac_secret'];
        }

        return [$attributes, $revealed];
    }

    /**
     * Test de connectivité : POST signé vers /api/plugin/ping du partenaire.
     * Feedback immédiat via session flash. Sortant uniquement.
     * L'appel trace une delivery event_type=__test__.ping (filtrable dans le log).
     */
    public function testConnection(IntegrationEndpoint $endpoint): RedirectResponse
    {
        abort_unless($endpoint->direction === IntegrationEndpoint::DIRECTION_OUTBOUND, 404);

        if (empty($endpoint->url)) {
            return back()->with('error', 'URL cible manquante — configure-la avant de tester.');
        }

        try {
            $started = microtime(true);
            $response = (new N2PClient($endpoint))->pushPing();
            $ms = (int) ((microtime(true) - $started) * 1000);

            return back()->with('success', "✅ Ping OK ({$ms} ms) — réponse : " . json_encode($response, JSON_UNESCAPED_UNICODE));
        } catch (RequestException $e) {
            $status = $e->response?->status();
            $body = mb_strimwidth((string) $e->response?->body(), 0, 300, '…');
            return back()->with('error', "❌ Ping échoué (HTTP {$status}) : {$body}");
        } catch (Throwable $e) {
            return back()->with('error', '❌ Ping échoué : ' . $e->getMessage());
        }
    }

    /**
     * Rejoue N derniers jours de réceptions de tôles vers l'endpoint N2P sortant.
     * Réservé à l'endpoint (n2p, outbound) — bouton visible uniquement dans ce cas.
     */
    public function resyncSheets(Request $request, IntegrationEndpoint $endpoint): RedirectResponse
    {
        abort_unless(
            $endpoint->system_code === 'n2p' && $endpoint->direction === IntegrationEndpoint::DIRECTION_OUTBOUND,
            404,
        );

        $days = (int) $request->input('days', 30);
        $days = max(1, min(365, $days));

        SyncAllSheetLotsToN2P::dispatch($days);

        return redirect()
            ->route('admin.integrations.endpoints.edit', $endpoint)
            ->with('success', "Resync catalogue tôles ({$days} j) mis en queue.");
    }

    public function deliveries(Request $request, IntegrationEndpoint $endpoint): View
    {
        $query = IntegrationDelivery::query()->forEndpoint($endpoint->id);

        if ($direction = $request->query('direction')) {
            if (in_array($direction, ['in', 'out'], true)) {
                $query->where('direction', $direction);
            }
        }
        if ($eventType = $request->query('event_type')) {
            $query->where('event_type', 'like', $eventType . '%');
        }
        if ($request->query('status') === 'failed') {
            $query->whereNotNull('error');
        }
        if ($request->query('status') === 'pending') {
            $query->pending();
        }

        $deliveries = $query->orderByDesc('created_at')->paginate(50)->withQueryString();

        return view('integrations.endpoints.deliveries', [
            'endpoint'   => $endpoint,
            'deliveries' => $deliveries,
        ]);
    }

    /**
     * Merge validated input while preserving secrets when placeholder submitted.
     */
    private function prepareAttributes(IntegrationEndpointRequest $request, ?IntegrationEndpoint $existing): array
    {
        $data = $request->validated();

        $data['bearer_token'] = $this->resolveSecret(
            submitted: $request->input('bearer_token'),
            existing: $existing?->bearer_token,
            authMethods: [IntegrationEndpoint::AUTH_BEARER, IntegrationEndpoint::AUTH_BEARER_HMAC],
            currentAuth: $data['auth_method'],
        );

        $data['hmac_secret'] = $this->resolveSecret(
            submitted: $request->input('hmac_secret'),
            existing: $existing?->hmac_secret,
            authMethods: [IntegrationEndpoint::AUTH_HMAC, IntegrationEndpoint::AUTH_BEARER_HMAC],
            currentAuth: $data['auth_method'],
        );

        if (! array_key_exists('retry_backoff', $data)) {
            if ($existing === null) {
                $data['retry_backoff'] = IntegrationEndpoint::DEFAULT_RETRY_BACKOFF;
            }
        } elseif ($data['retry_backoff'] === []) {
            $data['retry_backoff'] = IntegrationEndpoint::DEFAULT_RETRY_BACKOFF;
        }

        return $data;
    }

    private function resolveSecret(mixed $submitted, ?string $existing, array $authMethods, string $currentAuth): ?string
    {
        if (! in_array($currentAuth, $authMethods, true)) {
            return null;
        }
        if ($submitted === null || $submitted === '' || $submitted === self::SECRET_PLACEHOLDER) {
            return $existing;
        }
        return (string) $submitted;
    }
}
