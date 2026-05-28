<?php
namespace App\Services;

use App\Models\Workflow\Invoices;

class InvoiceCalculatorService
{
    private $invoices;

    public $TotalPrice;
    public $SubTotal;
    public $VatTotal;

    public function __construct(Invoices $invoices)
    {
        $this->invoices = $invoices;
    }

    /**
     * Résout le prix unitaire, remise et taux TVA pour une ligne.
     * Utilise le snapshot stocké sur invoice_lines en priorité ;
     * repli sur orderLine pour les lignes antérieures à la migration.
     */
    private function lineSnapshot($invoicesLine): array
    {
        $unitPrice = $invoicesLine->unit_price ?? $invoicesLine->orderLine->selling_price;
        $discount  = $invoicesLine->discount  ?? $invoicesLine->orderLine->discount;
        $vatRate   = $invoicesLine->vat_rate  ?? ($invoicesLine->orderLine->VAT['rate'] ?? 0);
        $vatId     = $invoicesLine->orderLine->accounting_vats_id;

        return [$unitPrice, $discount, $vatRate, $vatId];
    }

    public function getVatTotal()
    {
        $tableauTVA    = [];
        $invoicesLines = $this->invoices->invoiceLines;

        foreach ($invoicesLines as $invoicesLine) {
            [$unitPrice, $discount, $vatRate, $vatId] = $this->lineSnapshot($invoicesLine);

            $subtotalLine  = $invoicesLine->qty * $unitPrice * (1 - $discount / 100);
            $vatAmountLine = $subtotalLine * ($vatRate / 100);

            if (array_key_exists($vatId, $tableauTVA)) {
                $tableauTVA[$vatId][1] += $vatAmountLine;
            } else {
                $tableauTVA[$vatId] = [$vatRate, $vatAmountLine];
            }
        }

        asort($tableauTVA);
        return $tableauTVA;
    }

    public function getTotalPrice()
    {
        $TotalPrice    = 0;
        $invoicesLines = $this->invoices->invoiceLines;

        foreach ($invoicesLines as $invoicesLine) {
            [$unitPrice, $discount, $vatRate] = $this->lineSnapshot($invoicesLine);

            $subtotalLine = $invoicesLine->qty * $unitPrice * (1 - $discount / 100);
            $TotalPrice  += $subtotalLine + $subtotalLine * ($vatRate / 100);
        }

        return $TotalPrice;
    }

    public function getSubTotal()
    {
        $SubTotal      = 0;
        $invoicesLines = $this->invoices->invoiceLines;

        foreach ($invoicesLines as $invoicesLine) {
            [$unitPrice, $discount] = $this->lineSnapshot($invoicesLine);

            $SubTotal += round($invoicesLine->qty * $unitPrice * (1 - $discount / 100), 2);
        }

        return $SubTotal;
    }
}
