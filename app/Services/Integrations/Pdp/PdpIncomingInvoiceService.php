<?php

namespace App\Services\Integrations\Pdp;

use App\Models\Companies\Companies;
use App\Models\Integrations\PdpIncomingInvoice;
use App\Models\Purchases\PurchaseInvoice;
use App\Services\DocumentCodeGenerator;
use App\Services\Integrations\Pdp\Data\IncomingInvoiceData;
use App\Services\Integrations\Pdp\Contracts\PdpStatusReportingGateway;
use App\Services\Integrations\Pdp\Enums\PdpOutgoingStatus;
use App\Services\Integrations\Pdp\Inbound\FacturXReader;
use Illuminate\Support\Facades\Log;

/**
 * Réception des factures fournisseurs entrantes : lit le Factur-X, rapproche le
 * fournisseur, dépose le document dans la boîte de réception (staging), puis
 * permet sa conversion en facture d'achat (PurchaseInvoice).
 *
 * Agnostique de la PDP : le contenu (PDF/A-3 ou XML CII) peut provenir d'un
 * webhook fournisseur, d'un fetch périodique, ou d'un dépôt manuel.
 */
class PdpIncomingInvoiceService
{
    public function __construct(
        private FacturXReader $reader,
        private DocumentCodeGenerator $codeGenerator,
        private PdpManager $manager,
    ) {}

    /**
     * Déclare un statut à la plateforme sur une facture fournisseur reçue.
     *
     * Obligation de l'acheteur : le fournisseur doit savoir où en est sa
     * facture, et l'administration en déduit l'exigibilité de la TVA sur les
     * prestations de services. Ce n'est pas une courtoisie.
     *
     * @throws \RuntimeException si le document ne vient pas d'une plateforme,
     *                           ou si celle-ci refuse la déclaration
     */
    public function reportStatus(
        PdpIncomingInvoice $incoming,
        PdpOutgoingStatus $status,
        ?string $reason = null,
        ?string $note = null,
    ): void {
        if (! $incoming->external_id) {
            throw new \RuntimeException(
                "Ce document a été déposé à la main : il n'existe pas chez une plateforme, "
                . 'aucun statut ne peut y être déclaré.'
            );
        }

        $gateway = $this->manager->driver($incoming->provider);

        if (! $gateway instanceof PdpStatusReportingGateway) {
            throw new \RuntimeException(
                "La plateforme [{$incoming->provider}] n'accepte pas la déclaration de statuts."
            );
        }

        $gateway->reportStatus($incoming->external_id, $status, $reason, $note);

        // Un refus déclaré au fournisseur doit se voir dans la boîte de
        // réception : sans cela, le document resterait « à traiter ».
        if ($status === PdpOutgoingStatus::Refused) {
            $incoming->update(['status' => PdpIncomingInvoice::STATUS_REJECTED]);
        }

        Log::info('PdpIncoming: status reported to platform', [
            'incoming_id' => $incoming->id,
            'status'      => $status->value,
        ]);
    }

    /** La plateforme d'origine accepte-t-elle une déclaration de statut ? */
    public function canReportStatus(PdpIncomingInvoice $incoming): bool
    {
        if (! $incoming->external_id) {
            return false;
        }

        try {
            return $this->manager->driver($incoming->provider) instanceof PdpStatusReportingGateway;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Ingère un document Factur-X entrant. Idempotent : un même n° de facture
     * pour un même vendeur n'est enregistré qu'une fois.
     *
     * @param string  $content    PDF/A-3 ou XML CII brut
     * @param string  $source     'qonto', 'manual'…
     * @param ?string $externalId identifiant côté PDP, si applicable
     *
     * @throws \Throwable si le document n'est pas lisible
     */
    public function ingest(string $content, string $source = 'manual', ?string $externalId = null): PdpIncomingInvoice
    {
        $data = $this->reader->read($content);

        $sellerVat = $this->normalizeId($data->sellerVatId);

        // Dédoublonnage sur (vendeur, n° de facture).
        $existing = PdpIncomingInvoice::query()
            ->where('seller_vat', $sellerVat)
            ->where('invoice_number', $data->invoiceNumber)
            ->first();

        if ($existing) {
            Log::info('PdpIncoming: duplicate ignored', [
                'invoice_number' => $data->invoiceNumber,
                'seller_vat'     => $sellerVat,
            ]);
            return $existing;
        }

        $supplier = $this->resolveSupplier($data);

        return PdpIncomingInvoice::create([
            'provider'            => $source,
            'external_id'         => $externalId,
            'supplier_company_id' => $supplier?->id,
            'seller_name'         => $data->sellerName,
            'seller_vat'          => $sellerVat,
            'seller_legal_id'     => $this->normalizeId($data->sellerLegalId),
            'invoice_number'      => $data->invoiceNumber,
            'issue_date'          => $data->issueDate,
            'due_date'            => $data->dueDate,
            'currency'            => $data->currency,
            'total_ht'            => $data->totalHt,
            'total_vat'           => $data->totalVat,
            'total_ttc'           => $data->totalTtc,
            'buyer_reference'     => $data->buyerReference,
            'status'              => $supplier
                ? PdpIncomingInvoice::STATUS_RECEIVED
                : PdpIncomingInvoice::STATUS_SUPPLIER_UNMATCHED,
            'payload'             => $data->toArray(),
        ]);
    }

    /**
     * Convertit une facture entrante rapprochée en facture d'achat (en-tête).
     * Les lignes restent à rapprocher avec les réceptions via le flux d'achat.
     *
     * @throws \RuntimeException si le fournisseur n'est pas rapproché ou déjà converti
     */
    public function convertToPurchaseInvoice(PdpIncomingInvoice $incoming, int $userId): PurchaseInvoice
    {
        if (! $incoming->supplier_company_id) {
            throw new \RuntimeException('Fournisseur non rapproché : conversion impossible.');
        }
        if ($incoming->status === PdpIncomingInvoice::STATUS_CONVERTED && $incoming->purchase_invoice_id) {
            return $incoming->purchaseInvoice;
        }

        $this->ensureSupplierStatus($incoming->supplier);

        $invoice = PurchaseInvoice::create([
            'code'               => $this->codeGenerator->generateDocumentCode('purchase-invoice'),
            'label'              => $incoming->invoice_number ?? 'Facture fournisseur',
            'supplier_reference' => $incoming->invoice_number,
            'companies_id'       => $incoming->supplier_company_id,
            'user_id'            => $userId,
            'statu'              => 1,
        ]);

        $this->attachPurchaseInvoice($incoming, $invoice);

        return $invoice;
    }

    /**
     * Rattache une facture d'achat existante au document reçu.
     *
     * Utilisé par le rapprochement : la facture d'achat est construite à partir
     * des lignes de réception sélectionnées (écran « en attente de facturation »),
     * puis rattachée ici au document électronique qui l'a motivée. Le document
     * cesse alors d'apparaître comme à traiter.
     */
    public function attachPurchaseInvoice(PdpIncomingInvoice $incoming, PurchaseInvoice $invoice): void
    {
        $this->ensureSupplierStatus($incoming->supplier);

        $incoming->update([
            'status'              => PdpIncomingInvoice::STATUS_CONVERTED,
            'purchase_invoice_id' => $invoice->id,
        ]);

        Log::info('PdpIncoming: attached to purchase invoice', [
            'incoming_id'         => $incoming->id,
            'purchase_invoice_id' => $invoice->id,
        ]);
    }

    /**
     * Active le statut fournisseur de la société si elle ne l'a pas encore.
     *
     * Une même société peut être à la fois client et fournisseur, et les deux
     * statuts sont indépendants dans WEM. Le rapprochement se fait sur la TVA
     * ou le SIREN, sans filtrer sur le statut : une société connue comme client
     * est donc correctement identifiée quand elle nous facture.
     *
     * Sans cette activation, la facture d'achat créée porterait une société que
     * `statu_supplier = 2` exclut partout ailleurs — listes de sélection, KPI
     * achats, relances : la facture existerait sans apparaître nulle part.
     *
     * L'activation a lieu à la conversion, geste explicite de l'utilisateur, et
     * non à la réception : recevoir une facture de quelqu'un suffit à en faire
     * un fournisseur, mais c'est la conversion qui l'acte.
     */
    private function ensureSupplierStatus(?Companies $company): void
    {
        if (! $company || (int) $company->statu_supplier === 2) {
            return;
        }

        $company->update(['statu_supplier' => 2]);

        Log::info('PdpIncoming: supplier status activated', [
            'company_id' => $company->id,
            'label'      => $company->label,
        ]);
    }

    /** Rapproche le vendeur du Factur-X avec un fournisseur WEM (TVA puis SIREN). */
    private function resolveSupplier(IncomingInvoiceData $data): ?Companies
    {
        $vat   = $this->normalizeId($data->sellerVatId);
        $legal = $this->normalizeId($data->sellerLegalId);

        return Companies::query()
            ->when($vat, fn ($q) => $q->orWhereRaw('REPLACE(UPPER(intra_community_vat), " ", "") = ?', [$vat]))
            ->when($legal, fn ($q) => $q->orWhereRaw('REPLACE(siren, " ", "") = ?', [$legal]))
            ->when(! $vat && ! $legal, fn ($q) => $q->whereRaw('1 = 0'))
            ->first();
    }

    private function normalizeId(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $clean = strtoupper(preg_replace('/\s+/', '', $value));
        return $clean === '' ? null : $clean;
    }
}
