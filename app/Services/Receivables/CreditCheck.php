<?php

namespace App\Services\Receivables;

use App\Enums\CreditBlockLevel;

/**
 * Résultat du contrôle d'encours avant la création d'un document.
 */
final class CreditCheck
{
    /** Motif saisi quand l'utilisateur force le blocage. */
    public ?string $overrideReason = null;

    public function __construct(
        public readonly ?CreditExposure $exposure = null,
        public readonly float $additional = 0.0,
    ) {}

    /** Société sans plafond ou sans contrôle : rien à vérifier. */
    public static function clear(): self
    {
        return new self();
    }

    public function level(): CreditBlockLevel
    {
        return $this->exposure?->level ?? CreditBlockLevel::None;
    }

    public function projected(): float
    {
        return round(($this->exposure?->total() ?? 0.0) + $this->additional, 2);
    }

    public function exceeded(): bool
    {
        if ($this->exposure === null || ! $this->exposure->hasLimit() || $this->level() === CreditBlockLevel::None) {
            return false;
        }

        return $this->projected() > $this->exposure->limit + ReceivablesService::EPSILON;
    }
}
