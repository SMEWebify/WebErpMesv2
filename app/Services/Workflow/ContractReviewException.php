<?php

namespace App\Services\Workflow;

/**
 * Refus lié à la revue de contrat ; le message, traduit, est destiné à
 * l'utilisateur, le code est le statut HTTP à renvoyer (403, 409, 422).
 */
class ContractReviewException extends \DomainException
{
}
