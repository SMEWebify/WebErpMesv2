<?php

namespace App\Enums;

/**
 * Étape d'une affaire (opportunité) sur le parcours d'une TPE de métallerie :
 * visite → définition de l'ouvrage → devis → acceptation → achats et planning
 * → réalisation → facturation.
 *
 * L'étape est déduite des documents rattachés (AffairSummaryService), jamais
 * saisie : elle ne peut donc pas contredire l'état réel de l'affaire.
 */
enum AffairStage: string
{
    case Visit       = 'visit';
    case Definition  = 'definition';
    case Quote       = 'quote';
    case Acceptance  = 'acceptance';
    case Procurement = 'procurement';
    case Production  = 'production';
    case Invoicing   = 'invoicing';
    case Closed      = 'closed';
    case Lost        = 'lost';

    /**
     * Étapes du parcours, dans l'ordre. Closed et Lost sont des issues,
     * pas des jalons : elles n'apparaissent pas sur la frise.
     *
     * @return string[]
     */
    public static function path(): array
    {
        return [
            self::Visit->value,
            self::Definition->value,
            self::Quote->value,
            self::Acceptance->value,
            self::Procurement->value,
            self::Production->value,
            self::Invoicing->value,
        ];
    }
}
