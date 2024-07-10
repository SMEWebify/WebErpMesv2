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
    private $invoices;

    public $TotalPrice;
    public $SubTotal;
    public $VatTotal;

    public function __construct(CreditNotes $invoices)
    {
        $this->invoices = $invoices;
    }

    public function getVatTotal()
    {
        $tableauTVA = array();
        $invoicesLines = $this->invoices->invoiceLines;
        foreach ($invoicesLines as $invoicesLine) {
            $TotalCurentLine = ($invoicesLine->qty*$invoicesLine->orderLine->selling_price)-($invoicesLine->qty*$invoicesLine->orderLine->selling_price)*($invoicesLine->orderLine->discount/100);
			$TotalVATCurentLine =  $TotalCurentLine*($invoicesLine->orderLine->VAT['rate']/100) ;
            if(array_key_exists($invoicesLine->orderLine->accounting_vats_id, $tableauTVA)){
                $tableauTVA[$invoicesLine->orderLine->accounting_vats_id][1] += $TotalVATCurentLine;
            }
            else{
                $tableauTVA[$invoicesLine->orderLine->accounting_vats_id] = array($invoicesLine->orderLine->VAT['rate'], $TotalVATCurentLine);
            }
        }
        asort($tableauTVA);
        return $tableauTVA;
    }


    public function getTotalPrice()
    {
        $TotalPrice = 0;
        $invoicesLines = $this->invoices->invoiceLines;
        
        foreach ($invoicesLines as $invoicesLine) {
            $TotalPriceLine = ($invoicesLine->qty * $invoicesLine->orderLine->selling_price)-($invoicesLine->qty * $invoicesLine->orderLine->selling_price)*($invoicesLine->orderLine->discount/100);
            $TotalVATPrice = $TotalPriceLine*($invoicesLine->orderLine->VAT['rate']/100);
            $TotalPrice += $TotalPriceLine+$TotalVATPrice;

            
        }
        
        
        return $TotalPrice;
    }

    public function getSubTotal()
    {
        $SubTotal = 0;
        $invoicesLines = $this->invoices->invoiceLines;
        foreach ($invoicesLines as $invoicesLine) {
            $SubTotal += ($invoicesLine->qty * $invoicesLine->orderLine->selling_price)-($invoicesLine->qty * $invoicesLine->orderLine->selling_price)*($invoicesLine->orderLine->discount/100);
        }
        return $SubTotal;
    }

}