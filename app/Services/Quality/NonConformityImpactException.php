<?php

namespace App\Services\Quality;

/**
 * Refus lié à l'impact d'une NC (ordre de retouche, report) ; le message, traduit,
 * est destiné à l'utilisateur, le code est le statut HTTP à renvoyer (403, 422).
 */
class NonConformityImpactException extends \DomainException
{
}
