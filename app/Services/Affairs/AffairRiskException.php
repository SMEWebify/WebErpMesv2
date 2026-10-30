<?php

namespace App\Services\Affairs;

/**
 * Refus lié au registre des risques ; le message, traduit, est destiné à
 * l'utilisateur, le code est le statut HTTP à renvoyer (403, 422).
 */
class AffairRiskException extends \DomainException
{
}
