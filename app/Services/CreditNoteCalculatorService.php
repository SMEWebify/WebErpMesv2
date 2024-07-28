<?php
namespace App\Services;

use App\Repositories\Tax;
use App\Repositories\Money;
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

    public function getSubTotal()
    {
        $SubTotal = 0;
        $creditNotesLines = $this->creditNotes->creditNotelines;
        foreach ($creditNotesLines as $creditNotesLine) {
            $SubTotal += ($creditNotesLine->qty * $creditNotesLine->orderLine->selling_price)-($creditNotesLine->qty * $creditNotesLine->orderLine->selling_price)*($creditNotesLine->orderLine->discount/100);
        }
        return $SubTotal;
    }

}