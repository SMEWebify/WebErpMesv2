<?php

namespace App\Services\Integrations\Handlers;

use App\Models\Integrations\IntegrationEndpoint;
use App\Services\Integrations\IntegrationEventHandler;
use App\Services\Stock\NestRecorder;
use InvalidArgumentException;

/**
 * `nest.updated` de Nest2Prod MES : état complet d'une imbrication (pièces par OF,
 * chutes, format, utilisation). Rejouable : pièces remplacées, chutes écrites une
 * fois. La sortie de tôle reste portée par `stock.consumed`. Contrat :
 * docs/n2p-integration.md.
 */
class HandleNestEvent implements IntegrationEventHandler
{
    public const EVENT_UPDATED = 'nest.updated';

    public function __construct(private NestRecorder $recorder)
    {
    }

    public function handle(IntegrationEndpoint $endpoint, array $data, array $meta): void
    {
        $eventType = (string) ($meta['event_type'] ?? '');
        if ($eventType !== self::EVENT_UPDATED) {
            throw new InvalidArgumentException("Unsupported nest event: {$eventType}");
        }

        $this->recorder->upsertFromN2P($data);
    }
}
