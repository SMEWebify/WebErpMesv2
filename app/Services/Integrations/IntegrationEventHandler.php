<?php

namespace App\Services\Integrations;

use App\Models\Integrations\IntegrationEndpoint;

interface IntegrationEventHandler
{
    /**
     * @param  array<string, mixed>  $data   Contenu métier de l'event (payload.data)
     * @param  array<string, mixed>  $meta   event_id, event_type, occurred_at, source
     *
     * @throws \Throwable Toute exception marque la livraison en échec.
     */
    public function handle(IntegrationEndpoint $endpoint, array $data, array $meta): void;
}
