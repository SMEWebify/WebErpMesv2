<?php

namespace App\Services\Integrations\Pdp\Data;

use App\Services\Integrations\Pdp\Enums\PdpLifecycle;

/**
 * Événement de cycle de vie normalisé issu d'un webhook PDP (après vérification
 * de signature et traduction du vocabulaire fournisseur par le driver).
 */
final class PdpWebhookEvent
{
    public function __construct(
        public readonly string $externalId,
        public readonly PdpLifecycle $lifecycle,
        public readonly ?string $rejectionReason = null,
        public readonly array $raw = [],
    ) {}
}
