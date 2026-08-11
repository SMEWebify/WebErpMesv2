<?php

namespace App\Http\Controllers\Api\Integrations;

use App\Http\Controllers\Controller;
use App\Http\Middleware\VerifyIntegrationInbound;
use App\Models\Integrations\IntegrationEndpoint;
use App\Services\Integrations\IntegrationDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class IntegrationInboundController extends Controller
{
    public function __construct(private IntegrationDispatcher $dispatcher)
    {
    }

    public function handle(Request $request): JsonResponse
    {
        $endpoint = $request->attributes->get(VerifyIntegrationInbound::REQUEST_ATTR);
        if (! $endpoint instanceof IntegrationEndpoint) {
            return response()->json(['error' => 'endpoint_missing'], 500);
        }

        $payload = $request->validate([
            'event_id'    => ['required', 'uuid'],
            'event_type'  => ['required', 'string', 'max:100'],
            'occurred_at' => ['required', 'date'],
            'source'      => ['nullable', 'string', 'max:100'],
            'data'        => ['nullable', 'array'],
        ]);

        if (! $endpoint->subscribesTo($payload['event_type'])) {
            return response()->json([
                'status' => 'ignored',
                'reason' => 'event_type_not_subscribed',
                'event_type' => $payload['event_type'],
            ], 202);
        }

        try {
            $delivery = $this->dispatcher->dispatchInbound($endpoint, $payload);
        } catch (Throwable $e) {
            // Ne PAS renvoyer $e->getMessage() au partenaire : peut contenir
            // du SQL, des IDs internes ou des noms de table. On expose une
            // correlation_id que le support peut retrouver dans les logs.
            $correlation = (string) Str::uuid();
            Log::channel('n2p')->error('Inbound dispatch threw', [
                'correlation_id' => $correlation,
                'endpoint_id' => $endpoint->id,
                'event_id' => $payload['event_id'] ?? null,
                'event_type' => $payload['event_type'] ?? null,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'failed',
                'event_id' => $payload['event_id'],
                'error' => 'dispatch_failed',
                'correlation_id' => $correlation,
            ], 500);
        }

        // no_handler = event dont on ne sait rien faire côté ERP. Traité
        // comme "ignored" (200) et non "failed" (422) : ça préserve la
        // forward-compat quand N2P déploie un nouveau type d'event avant
        // que l'ERP ne l'implémente — inutile de faire retenter N2P en
        // boucle sur quelque chose qui ne sera jamais traité.
        $isNoHandler = $delivery->error !== null
            && str_starts_with((string) $delivery->error, 'no_handler:');

        $status = match (true) {
            $delivery->processed_at !== null && $delivery->error === null => 'processed',
            $isNoHandler                                                  => 'ignored',
            $delivery->error !== null                                     => 'failed',
            default                                                       => 'accepted',
        };

        $httpCode = $status === 'failed' ? 422 : 200;

        return response()->json([
            'status' => $status,
            'delivery_id' => $delivery->id,
            'event_id' => $delivery->event_id,
            'processed_at' => $delivery->processed_at?->toIso8601String(),
            // error côté delivery peut aussi contenir des internals — on
            // renvoie seulement une catégorie stable côté partenaire.
            'error' => match (true) {
                $isNoHandler              => 'no_handler',
                $delivery->error !== null => 'handler_failed',
                default                   => null,
            },
        ], $httpCode);
    }
}
