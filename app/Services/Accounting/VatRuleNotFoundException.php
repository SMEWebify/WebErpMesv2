<?php

namespace App\Services\Accounting;

/**
 * Levée quand un tiers a un régime de TVA et une ligne une nature, mais qu'aucune
 * règle de matrice ne couvre ce couple : configuration incomplète à corriger dans
 * Comptabilité → Matrice TVA, plutôt qu'un repli silencieux sur un taux faux.
 */
class VatRuleNotFoundException extends \RuntimeException
{
}
