<?php

namespace App\Services\Integrations\Pdp\Contracts;

use App\Models\Workflow\Invoices;
use App\Services\Integrations\Pdp\Data\PdpInvoiceResult;
use App\Services\Integrations\Pdp\Data\PdpWebhookEvent;
use Illuminate\Http\Request;

/**
 * Contrat d'une Plateforme de Dématérialisation Partenaire (PDP).
 *
 * Un driver encapsule TOUT ce qui est spécifique au fournisseur : appels HTTP,
 * authentification, format de payload, vocabulaire de statuts, signature des
 * webhooks. L'orchestration WEM (persistance, statut interne, events) reste
 * dans PdpInvoiceService et ne dépend que de ce contrat.
 */
interface PdpGateway
{
    /** Identifiant court du fournisseur (ex: 'qonto'), stocké en base. */
    public function key(): string;

    /** L'intégration est-elle configurée/activée pour ce fournisseur ? */
    public function isEnabled(): bool;

    /** Dépose la facture (Factur-X) auprès de la PDP. */
    public function submit(Invoices $invoice): PdpInvoiceResult;

    /** Interroge le statut d'une facture déjà déposée. */
    public function poll(string $externalId, int $tenantId): PdpInvoiceResult;

    /**
     * Vérifie et traduit un webhook entrant en événement normalisé.
     * Retourne null si l'événement n'est pas pertinent (à acquitter sans action).
     *
     * @throws \App\Services\Integrations\Pdp\Exceptions\PdpSignatureException si la signature est invalide
     */
    public function parseWebhook(Request $request): ?PdpWebhookEvent;
}
