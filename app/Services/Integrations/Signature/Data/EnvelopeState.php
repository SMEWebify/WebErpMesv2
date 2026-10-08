<?php

namespace App\Services\Integrations\Signature\Data;

use App\Models\Workflow\QuoteSignature;
use Carbon\CarbonImmutable;

/**
 * État d'une enveloppe lu chez le prestataire, traduit dans le vocabulaire de
 * QuoteSignature (sent / delivered / completed / declined / voided).
 */
final class EnvelopeState
{
    public function __construct(
        public readonly string $status,
        public readonly ?CarbonImmutable $completedAt = null,
        public readonly ?string $reason = null,
    ) {}

    public function isCompleted(): bool
    {
        return $this->status === QuoteSignature::STATUS_COMPLETED;
    }
}
