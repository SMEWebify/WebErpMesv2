<?php

namespace App\Enums;

/**
 * Unité du décalage d'un lien entre opérations : des heures ouvrées, ou un
 * nombre de pièces (lot de transfert), converti en heures avec les temps de
 * la gamme au moment du calcul.
 */
enum TaskDependencyLagUnit: string
{
    case Hour  = 'hour';
    case Piece = 'piece';

    /** @return string[] */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
