<?php

namespace App\Services\Quotes;

use RuntimeException;

/**
 * Marge globale refusée (devis converti ou non modifiable) ; le message, traduit, est affiché tel quel.
 */
class QuoteMarginRefused extends RuntimeException
{
}
