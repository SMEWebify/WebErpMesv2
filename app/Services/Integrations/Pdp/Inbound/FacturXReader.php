<?php

namespace App\Services\Integrations\Pdp\Inbound;

use App\Services\Integrations\Pdp\Data\IncomingInvoiceData;
use App\Services\Integrations\Pdp\Data\IncomingInvoiceLine;
use horstoeko\zugferd\ZugferdDocumentPdfReader;
use horstoeko\zugferd\ZugferdDocumentReader;

/**
 * Lit un document Factur-X entrant (PDF/A-3 avec XML embarqué, ou XML CII brut)
 * et le normalise en IncomingInvoiceData. Indépendant de la PDP : tout document
 * conforme EN 16931 est lisible, quelle que soit la plateforme d'origine.
 */
class FacturXReader
{
    /**
     * @throws \Throwable si le contenu n'est pas un Factur-X / CII lisible
     */
    public function read(string $content): IncomingInvoiceData
    {
        $reader = $this->resolveReader($content);

        $reader->getDocumentInformation(
            $invoiceNo, $typeCode, $issueDate, $currency,
            $taxCurrency, $docName, $docLang, $effective
        );

        $reader->getDocumentSeller($sellerName, $sellerIds, $sellerDesc);
        $reader->getDocumentSellerTaxRegistration($sellerTaxReg);
        $reader->getDocumentSellerLegalOrganisation($sellerLegalId, $sellerLegalType, $sellerLegalName);
        $reader->getDocumentSellerAddress($l1, $l2, $l3, $postCode, $city, $sellerCountry, $subDiv);
        $reader->getDocumentBuyer($buyerName, $buyerIds, $buyerDesc);

        $buyerReference = null;
        try {
            $reader->getDocumentBuyerReference($buyerReference);
        } catch (\Throwable $e) {
            // BT-10 absent : non bloquant
        }

        $reader->getDocumentSummation(
            $grandTotal, $duePayable, $lineTotal,
            $chargeTotal, $allowanceTotal, $taxBasis, $taxTotal, $rounding, $prepaid
        );

        return new IncomingInvoiceData(
            invoiceNumber:    $invoiceNo,
            documentTypeCode: $typeCode,
            issueDate:        $issueDate instanceof \DateTimeInterface ? $issueDate->format('Y-m-d') : null,
            dueDate:          $this->readDueDate($reader),
            currency:         $currency,
            sellerName:       $sellerName,
            sellerVatId:      is_array($sellerTaxReg) ? ($sellerTaxReg['VA'] ?? null) : null,
            sellerLegalId:    $sellerLegalId ?: (is_array($sellerIds) ? ($sellerIds[0] ?? null) : null),
            sellerCountry:    $sellerCountry,
            buyerName:        $buyerName,
            buyerReference:   $buyerReference,
            totalHt:          $taxBasis !== null ? (float) $taxBasis : ($lineTotal !== null ? (float) $lineTotal : null),
            totalVat:         $taxTotal !== null ? (float) $taxTotal : null,
            totalTtc:         $grandTotal !== null ? (float) $grandTotal : null,
            lines:            $this->readLines($reader),
        );
    }

    private function resolveReader(string $content): ZugferdDocumentReader
    {
        // Un PDF commence par "%PDF" ; sinon on suppose du XML CII.
        if (str_starts_with(ltrim($content), '%PDF')) {
            return ZugferdDocumentPdfReader::readAndGuessFromContent($content);
        }

        return ZugferdDocumentReader::readAndGuessFromContent($content);
    }

    /** Date d'échéance via les conditions de paiement (BT-9), si présente. */
    private function readDueDate(ZugferdDocumentReader $reader): ?string
    {
        try {
            if (! $reader->firstDocumentPaymentTerms()) {
                return null;
            }
            do {
                $reader->getDocumentPaymentTerm($desc, $dueDate, $mandate);
                if ($dueDate instanceof \DateTimeInterface) {
                    return $dueDate->format('Y-m-d');
                }
            } while ($reader->nextDocumentPaymentTerms());
        } catch (\Throwable $e) {
            // Conditions de paiement absentes : non bloquant
        }

        return null;
    }

    /**
     * @return IncomingInvoiceLine[]
     */
    private function readLines(ZugferdDocumentReader $reader): array
    {
        $lines = [];

        if (! $reader->firstDocumentPosition()) {
            return $lines;
        }

        do {
            $reader->getDocumentPositionProductDetails($name, $desc, $sellerAssignedId, $buyerAssignedId, $globalType, $globalId);
            $reader->getDocumentPositionQuantity($qty, $unitCode, $cfQty, $cfUnit, $pkgQty, $pkgUnit);
            $reader->getDocumentPositionLineSummation($lineTotal, $totalAllowanceCharge);

            $lines[] = new IncomingInvoiceLine(
                name:             (string) ($name ?? ''),
                sellerAssignedId: $sellerAssignedId,
                quantity:         $qty !== null ? (float) $qty : 0.0,
                unitCode:         $unitCode,
                lineTotal:        $lineTotal !== null ? (float) $lineTotal : 0.0,
            );
        } while ($reader->nextDocumentPosition());

        return $lines;
    }
}
