<?php
namespace App\Services;

use App\Models\Workflow\Quotes;
use App\Repositories\Money;
use App\Repositories\Tax;

class QuoteCalculator
{
    /**
     * @var Quote
     */
    private $quote;

    public $TotalPrice;
    public $SubTotal;
    public $VatTotal;

    public function __construct(Quotes $quote)
    {
        $this->quote = $quote;
    }

    public function getVatTotal()
    {
        $tableauTVA = array();
        $quoteLines = $this->quote->quoteLines;

        foreach ($quoteLines as $quoteLine) {

            $TotalLigneHTEnCours = ($quoteLine->qty*$quoteLine->selling_price)-($quoteLine->qty*$quoteLine->selling_price)*($quoteLine->discount/100);
			$TotalLigneTVAEnCours =  $TotalLigneHTEnCours*($quoteLine->VAT['RATE']/100) ;

            if(array_key_exists($quoteLine->accounting_vats_id, $tableauTVA)){
                $tableauTVA[$quoteLine->accounting_vats_id][1] += $TotalLigneTVAEnCours;
            }
            else{
                $tableauTVA[$quoteLine->accounting_vats_id] = array($quoteLine->VAT['RATE'], $TotalLigneTVAEnCours);
            }
        }

        asort($tableauTVA);

        return $tableauTVA;
    }


    public function getTotalPrice()
    {
        $TotalPrice = 0;
        $quoteLines = $this->quote->quoteLines;

        foreach ($quoteLines as $quoteLine) {
            $TotalPriceLine = ($quoteLine->qty * $quoteLine->selling_price)-($quoteLine->qty * $quoteLine->selling_price)*($quoteLine->discount/100);
            $TotalVATPrice = $TotalPriceLine*($quoteLine->VAT['RATE']/100);
            $TotalPrice += $TotalPriceLine+$TotalVATPrice;
        }
       
        return $TotalPrice;
    }

    public function getSubTotal()
    {
        $SubTotal = 0;
        $quoteLines = $this->quote->quoteLines;

        foreach ($quoteLines as $quoteLine) {
            $SubTotal += ($quoteLine->qty * $quoteLine->selling_price)-($quoteLine->qty * $quoteLine->selling_price)*($quoteLine->discount/100);
        }
        return $SubTotal;
    }

}