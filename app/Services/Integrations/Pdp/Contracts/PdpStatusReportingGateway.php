<?php

namespace App\Services\Integrations\Pdp\Contracts;

use App\Services\Integrations\Pdp\Enums\PdpOutgoingStatus;

/**
 * Contrat OPTIONNEL pour les PDP acceptant que nous **émettions** des statuts.
 *
 * Symétrique de PdpGateway : celui-ci décrit ce que nous apprenons du sort de
 * nos factures, celui-là ce que nous devons déclarer sur les factures de nos
 * fournisseurs. L'obligation est réelle et pèse sur l'acheteur.
 */
interface PdpStatusReportingGateway
{
    /**
     * Déclare un statut sur une facture reçue.
     *
     * @param string  $externalId identifiant de la facture chez la plateforme
     * @param ?string $reason     code motif AFNOR (MDT-113), requis pour un refus
     * @param ?string $note       précision libre destinée au fournisseur
     *
     * @throws \RuntimeException si la plateforme refuse la déclaration
     */
    public function reportStatus(
        string $externalId,
        PdpOutgoingStatus $status,
        ?string $reason = null,
        ?string $note = null,
    ): void;
}
