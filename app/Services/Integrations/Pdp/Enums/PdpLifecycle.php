<?php

namespace App\Services\Integrations\Pdp\Enums;

/**
 * Statuts canoniques du cycle de vie d'une facture électronique, indépendants
 * de la PDP. Chaque driver (Qonto, etc.) traduit son propre vocabulaire vers
 * ces valeurs ; l'orchestration WEM ne raisonne que sur cet enum.
 */
enum PdpLifecycle: string
{
    case Pending      = 'pending';      // non encore soumise
    case Submitted    = 'submitted';    // déposée chez la PDP
    case Acknowledged = 'acknowledged'; // accusé de réception PDP
    case Rejected     = 'rejected';     // rejetée (format / données invalides)
    case Refused      = 'refused';      // refusée par le destinataire
    case Accepted     = 'accepted';     // acceptée par le destinataire
    case Paid         = 'paid';         // encaissée

    /**
     * Correspondance vers le statut interne WEM (colonne invoices.statu).
     * null = pas de changement de statut côté WEM.
     */
    public function toWemStatus(): ?int
    {
        return match ($this) {
            self::Pending                   => null,
            self::Submitted, self::Acknowledged => 2, // Envoyée
            self::Rejected                  => 1,     // Retour en brouillon
            self::Refused                   => 4,     // Impayée
            self::Accepted                  => 3,     // En attente de paiement
            self::Paid                      => 5,     // Payée
        };
    }
}
