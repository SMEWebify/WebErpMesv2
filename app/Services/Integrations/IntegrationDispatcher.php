<?php

namespace App\Services\Integrations;

use App\Models\Integrations\IntegrationDelivery;
use App\Models\Integrations\IntegrationEndpoint;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class IntegrationDispatcher
{
    public function __construct(private Container $container)
    {
    }

    /**
     * Dispatch un event entrant : dédup, résolution du handler, exécution.
     *
     * @param  array<string, mixed>  $payload  event canonique (event_id/type/occurred_at/data)
     */
    public function dispatchInbound(IntegrationEndpoint $endpoint, array $payload): IntegrationDelivery
    {
        $eventId = (string) ($payload['event_id'] ?? '');
        $eventType = (string) ($payload['event_type'] ?? '');
        $occurredAt = $this->parseTimestamp($payload['occurred_at'] ?? null);

        $existing = IntegrationDelivery::query()
            ->inbound()
            ->where('event_id', $eventId)
            ->first();

        if ($existing && $existing->processed_at !== null) {
            return $existing;
        }

        $delivery = $existing ?? $this->createDelivery($endpoint, $eventId, $eventType, $occurredAt, $payload);

        $handler = $this->resolveHandler($eventType);
        if ($handler === null) {
            $delivery->markFailed('no_handler:' . $eventType);
            Log::warning('No handler registered for integration event', [
                'endpoint_id' => $endpoint->id,
                'event_type' => $eventType,
                'event_id' => $eventId,
            ]);
            return $delivery;
        }

        // Serialise deux workers concurrents sur le même event_id : le second
        // attend la transaction du premier puis observe processed_at != null
        // et sort en no-op. La transaction rollback aussi les side-effects du
        // handler si celui-ci throw après une écriture partielle, ce qui rend
        // le retry N2P sûr (pas de doublon task_activity / stock_move).
        try {
            DB::transaction(function () use ($delivery, $handler, $endpoint, $payload, $eventId, $eventType, $occurredAt) {
                $locked = IntegrationDelivery::query()
                    ->whereKey($delivery->id)
                    ->lockForUpdate()
                    ->first();

                if ($locked && $locked->processed_at !== null) {
                    return;
                }

                $handler->handle($endpoint, (array) ($payload['data'] ?? []), [
                    'event_id' => $eventId,
                    'event_type' => $eventType,
                    'occurred_at' => $occurredAt,
                    'source' => (string) ($payload['source'] ?? $endpoint->system_code),
                ]);

                $delivery->markProcessed();
            });
        } catch (Throwable $e) {
            $delivery->markFailed($e->getMessage());
            Log::error('Integration handler threw', [
                'endpoint_id' => $endpoint->id,
                'event_type' => $eventType,
                'event_id' => $eventId,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $delivery;
    }

    private function createDelivery(
        IntegrationEndpoint $endpoint,
        string $eventId,
        string $eventType,
        Carbon $occurredAt,
        array $payload,
    ): IntegrationDelivery {
        $attributes = [
            'integration_endpoint_id' => $endpoint->id,
            'direction' => IntegrationDelivery::DIRECTION_IN,
            'event_id' => $eventId,
            'event_type' => $eventType,
            'payload' => $this->truncatePayload($payload),
            'occurred_at' => $occurredAt,
        ];

        try {
            return IntegrationDelivery::create($attributes);
        } catch (UniqueConstraintViolationException $e) {
            return IntegrationDelivery::query()
                ->inbound()
                ->where('event_id', $eventId)
                ->firstOrFail();
        }
    }

    private function resolveHandler(string $eventType): ?IntegrationEventHandler
    {
        $handlers = (array) config('integrations.handlers', []);
        $class = $handlers[$eventType] ?? null;

        if (! is_string($class) || $class === '') {
            return null;
        }

        $instance = $this->container->make($class);
        if (! $instance instanceof IntegrationEventHandler) {
            Log::error('Configured handler does not implement IntegrationEventHandler', [
                'event_type' => $eventType,
                'class' => $class,
            ]);
            return null;
        }

        return $instance;
    }

    private function parseTimestamp(mixed $value): Carbon
    {
        if ($value === null || $value === '') {
            return Carbon::now();
        }

        try {
            return Carbon::parse((string) $value);
        } catch (Throwable) {
            return Carbon::now();
        }
    }

    private function truncatePayload(array $payload): array
    {
        $maxBytes = (int) config('integrations.payload_max_bytes', 65_000);
        $json = json_encode($payload);

        if ($json === false || strlen($json) <= $maxBytes) {
            return $payload;
        }

        return [
            '_truncated' => true,
            '_original_bytes' => strlen($json),
            'event_id' => $payload['event_id'] ?? null,
            'event_type' => $payload['event_type'] ?? null,
        ];
    }
}
