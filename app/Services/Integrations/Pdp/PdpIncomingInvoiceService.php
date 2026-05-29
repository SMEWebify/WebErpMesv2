<?php

namespace App\Services\Integrations\Pdp;

use App\Models\Companies\Companies;
use App\Models\Integrations\PdpIncomingInvoice;
use App\Models\Purchases\PurchaseInvoice;
use App\Services\DocumentCodeGenerator;
use App\Services\Integrations\Pdp\Data\IncomingInvoiceData;
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
    ) {}

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

        $invoice = PurchaseInvoice::create([
            'code'               => $this->codeGenerator->generateDocumentCode('purchase-invoice'),
            'label'              => $incoming->invoice_number ?? 'Facture fournisseur',
            'supplier_reference' => $incoming->invoice_number,
            'companies_id'       => $incoming->supplier_company_id,
            'user_id'            => $userId,
            'statu'              => 1,
        ]);

        $incoming->update([
            'status'              => PdpIncomingInvoice::STATUS_CONVERTED,
            'purchase_invoice_id' => $invoice->id,
        ]);

        Log::info('PdpIncoming: converted to purchase invoice', [
            'incoming_id'         => $incoming->id,
            'purchase_invoice_id' => $invoice->id,
        ]);

        return $invoice;
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
