<?php

namespace App\Services\Integrations\Pdp\Enums;

/**
 * Statuts que WEM doit **émettre** vers la plateforme, en tant qu'acheteur,
 * sur les factures fournisseurs reçues.
 *
 * La réforme française n'impose pas seulement de recevoir les factures : elle
 * oblige l'acheteur à renvoyer le cycle de vie du document. Un fournisseur doit
 * pouvoir savoir que sa facture a été prise en charge, approuvée, refusée, et
 * quand le paiement est parti. Ces statuts remontent aussi à l'administration
 * fiscale, qui en déduit l'exigibilité de la TVA sur les prestations de
 * services — d'où leur caractère obligatoire et non déclaratif.
 *
 * Les codes suivent la nomenclature AFNOR XP Z12-012.
 */
enum PdpOutgoingStatus: string
{
    case Acknowledged = 'fr:204'; // Prise en charge : le document est entré dans notre système
    case Approved     = 'fr:205'; // Approuvée : bonne à payer, en totalité
    case PartlyApproved = 'fr:206'; // Approuvée partiellement
    case Disputed     = 'fr:207'; // En litige : contestation ouverte, sans refus formel
    case Suspended    = 'fr:208'; // Suspendue : traitement en attente d'un élément
    case Refused      = 'fr:210'; // Refusée : le document ne sera pas payé en l'état
    case PaymentSent  = 'fr:211'; // Paiement transmis à la banque

    public function label(): string
    {
        return match ($this) {
            self::Acknowledged   => 'Prise en charge',
            self::Approved       => 'Approuvée',
            self::PartlyApproved => 'Approuvée partiellement',
            self::Disputed       => 'En litige',
            self::Suspended      => 'Suspendue',
            self::Refused        => 'Refusée',
            self::PaymentSent    => 'Paiement transmis',
        };
    }

    /**
     * Un motif est-il exigé ?
     *
     * Refuser ou contester sans dire pourquoi laisse le fournisseur sans moyen
     * de corriger : la norme impose un code motif (MDT-113) sur ces statuts.
     */
    public function requiresReason(): bool
    {
        return in_array($this, [self::Refused, self::Disputed, self::PartlyApproved], true);
    }
}
