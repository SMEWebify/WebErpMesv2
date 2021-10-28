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

    public function __construct(Quotes $quote)
    {
        $this->quote = $quote;
    }

    public function getVatTotal()
    {
        $VatTotal = $this->getSubTotal()->getTotalPrice();
        return $VatTotal;
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