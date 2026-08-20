<?php
namespace App\Services;

use App\Models\Workflow\CreditNotes;

class CreditNoteCalculatorService
{
    /**
     * @var CreditNotes
     */
    private $creditNotes;

    public $TotalPrice;
    public $SubTotal;
    public $VatTotal;

    public function __construct(CreditNotes $creditNotes)
    {
        $this->creditNotes = $creditNotes;
    }

    /**
     * Résout prix unitaire, remise, taux et identifiant de TVA d'une ligne d'avoir.
     *
     * Le prix vient du snapshot figé à la création de l'avoir, et non du prix
     * courant de la ligne de commande. Les lignes d'avoir portant sur une ligne
     * de facture libre n'ont d'ailleurs aucune ligne de commande derrière.
     */
    private function lineSnapshot($creditNotesLine): array
    {
        $unitPrice = (float) ($creditNotesLine->unit_price ?? $creditNotesLine->orderLine?->selling_price ?? 0);
        $discount  = $creditNotesLine->resolved_discount;
        $vatRate   = $creditNotesLine->resolved_vat_rate;

        // Clé de regroupement : à défaut d'identifiant de TVA, le taux.
        $vatId = $creditNotesLine->resolved_vat_id ?? 'rate-' . number_format($vatRate, 3, '.', '');

        return [$unitPrice, $discount, $vatRate, $vatId];
    }

    /**
     * Calculate the total VAT for the credit notes.
     *
     * This function iterates through the credit note lines and calculates the total VAT for each line.
     * It then aggregates the VAT totals by accounting VAT ID and returns an array with the VAT rate and total VAT amount.
     *
     * @return array An associative array where the key is the accounting VAT ID and the value is an array containing the VAT rate and the total VAT amount.
     */
    public function getVatTotal()
    {
        $tableauTVA = array();
        $creditNotesLines = $this->creditNotes->creditNotelines;

        foreach ($creditNotesLines as $creditNotesLine) {
            [$unitPrice, $discount, $vatRate, $vatId] = $this->lineSnapshot($creditNotesLine);

            $TotalCurentLine    = $creditNotesLine->qty * $unitPrice * (1 - $discount / 100);
            $TotalVATCurentLine = $TotalCurentLine * ($vatRate / 100);

            if (array_key_exists($vatId, $tableauTVA)) {
                $tableauTVA[$vatId][1] += $TotalVATCurentLine;
            } else {
                $tableauTVA[$vatId] = array($vatRate, $TotalVATCurentLine);
            }
        }

        asort($tableauTVA);
        return $tableauTVA;
    }


    /**
     * Calculate the total price of all credit note lines including VAT and discount.
     *
     * This method iterates through each credit note line, calculates the line total
     * by considering the quantity, selling price, and discount. It then adds the VAT
     * to the line total and accumulates the total price.
     *
     * @return float The total price of all credit note lines including VAT and discount.
     */
    public function getTotalPrice()
    {
        $TotalPrice = 0;
        $creditNotesLines = $this->creditNotes->creditNotelines;

        foreach ($creditNotesLines as $creditNotesLine) {
            [$unitPrice, $discount, $vatRate] = $this->lineSnapshot($creditNotesLine);

            $TotalPriceLine = $creditNotesLine->qty * $unitPrice * (1 - $discount / 100);
            $TotalVATPrice  = $TotalPriceLine * ($vatRate / 100);
            $TotalPrice    += $TotalPriceLine + $TotalVATPrice;
        }

        return $TotalPrice;
    }

    /**
     * Calculate the subtotal for the credit notes.
     *
     * This method iterates through the credit note lines and calculates the subtotal
     * by summing up the product of quantity and selling price for each line,
     * adjusted for any discounts.
     *
     * @return float The calculated subtotal for the credit notes.
     */
    public function getSubTotal()
    {
        $SubTotal = 0;
        $creditNotesLines = $this->creditNotes->creditNotelines;

        foreach ($creditNotesLines as $creditNotesLine) {
            [$unitPrice, $discount] = $this->lineSnapshot($creditNotesLine);

            $SubTotal += round($creditNotesLine->qty * $unitPrice * (1 - $discount / 100), 2);
        }

        return $SubTotal;
    }

}
