<?php

namespace App\Services\Integrations\Pdp\Data;

use App\Services\Integrations\Pdp\Enums\PdpLifecycle;

/**
 * Résultat normalisé d'un dépôt ou d'une interrogation de statut auprès d'une PDP.
 */
final class PdpInvoiceResult
{
    public function __construct(
        public readonly ?string $externalId,
        public readonly PdpLifecycle $lifecycle,
        public readonly ?string $rejectionReason = null,
        public readonly array $raw = [],
    ) {}
}
