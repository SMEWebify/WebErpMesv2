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
            $TotalCurentLine = ($creditNotesLine->qty*$creditNotesLine->orderLine->selling_price)-($creditNotesLine->qty*$creditNotesLine->orderLine->selling_price)*($creditNotesLine->orderLine->discount/100);
			$TotalVATCurentLine =  $TotalCurentLine*($creditNotesLine->orderLine->VAT['rate']/100) ;
            if(array_key_exists($creditNotesLine->orderLine->accounting_vats_id, $tableauTVA)){
                $tableauTVA[$creditNotesLine->orderLine->accounting_vats_id][1] += $TotalVATCurentLine;
            }
            else{
                $tableauTVA[$creditNotesLine->orderLine->accounting_vats_id] = array($creditNotesLine->orderLine->VAT['rate'], $TotalVATCurentLine);
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
            $TotalPriceLine = ($creditNotesLine->qty * $creditNotesLine->orderLine->selling_price)-($creditNotesLine->qty * $creditNotesLine->orderLine->selling_price)*($creditNotesLine->orderLine->discount/100);
            $TotalVATPrice = $TotalPriceLine*($creditNotesLine->orderLine->VAT['rate']/100);
            $TotalPrice += $TotalPriceLine+$TotalVATPrice;

            
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
            $SubTotal += round(
                $creditNotesLine->qty * $creditNotesLine->orderLine->selling_price * (1 - $creditNotesLine->orderLine->discount / 100),
                2
            );
        }
        return $SubTotal;
    }

}