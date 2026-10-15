<?php

namespace App\Enums;

/**
 * Conduite à tenir quand l'encours d'un client dépasse son plafond
 * (companies.credit_block_level). Null en base = aucune.
 */
enum CreditBlockLevel: string
{
    case None  = 'none';
    case Warn  = 'warn';
    case Block = 'block';

    public static function fromColumn(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::None;
    }
}
