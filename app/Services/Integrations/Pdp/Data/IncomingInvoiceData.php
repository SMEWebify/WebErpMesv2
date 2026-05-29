<?php

namespace App\Services\Integrations\Pdp\Data;

/**
 * Données normalisées d'une facture fournisseur entrante (Factur-X / EN 16931),
 * indépendantes de la PDP. Produit par FacturXReader, consommé par
 * PdpIncomingInvoiceService.
 */
final class IncomingInvoiceData
{
    /**
     * @param IncomingInvoiceLine[] $lines
     */
    public function __construct(
        public readonly ?string $invoiceNumber,
        public readonly ?string $documentTypeCode,
        public readonly ?string $issueDate,   // format Y-m-d
        public readonly ?string $dueDate,     // format Y-m-d
        public readonly ?string $currency,
        public readonly ?string $sellerName,
        public readonly ?string $sellerVatId,
        public readonly ?string $sellerLegalId, // SIREN/SIRET (schéma 0002/0009)
        public readonly ?string $sellerCountry,
        public readonly ?string $buyerName,
        public readonly ?string $buyerReference,
        public readonly ?float $totalHt,
        public readonly ?float $totalVat,
        public readonly ?float $totalTtc,
        public readonly array $lines = [],
    ) {}

    public function toArray(): array
    {
        return [
            'invoice_number'    => $this->invoiceNumber,
            'document_type'     => $this->documentTypeCode,
            'issue_date'        => $this->issueDate,
            'due_date'          => $this->dueDate,
            'currency'          => $this->currency,
            'seller_name'       => $this->sellerName,
            'seller_vat'        => $this->sellerVatId,
            'seller_legal_id'   => $this->sellerLegalId,
            'seller_country'    => $this->sellerCountry,
            'buyer_name'        => $this->buyerName,
            'buyer_reference'   => $this->buyerReference,
            'total_ht'          => $this->totalHt,
            'total_vat'         => $this->totalVat,
            'total_ttc'         => $this->totalTtc,
            'lines'             => array_map(fn ($l) => $l->toArray(), $this->lines),
        ];
    }
}
