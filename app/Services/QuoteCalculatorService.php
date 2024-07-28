<?php
namespace App\Services;

use App\Models\Workflow\Quotes;
use App\Repositories\Money;
use App\Repositories\Tax;

class QuoteCalculatorService
{
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
            $TotalCurentLine = ($quoteLine->qty*$quoteLine->selling_price)-($quoteLine->qty*$quoteLine->selling_price)*($quoteLine->discount/100);
			$TotalVATCurentLine =  $TotalCurentLine*($quoteLine->VAT['rate']/100) ;
            if(array_key_exists($quoteLine->accounting_vats_id, $tableauTVA)){
                $tableauTVA[$quoteLine->accounting_vats_id][1] += $TotalVATCurentLine;
            }
            else{
                $tableauTVA[$quoteLine->accounting_vats_id] = array($quoteLine->VAT['rate'], $TotalVATCurentLine);
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
            $TotalVATPrice = $TotalPriceLine*($quoteLine->VAT['rate']/100);
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
    
    public function getTotalProductTimeByService()
    {
        $tableauService = array();
        $quoteLines = $this->quote->quoteLines;
        foreach ($quoteLines as $quoteLine) {
            foreach ($quoteLine->TechnicalCut as $TechnicalCutLine) {
                $TotalServiceProductTimeForQuoteCurentLine =  $TechnicalCutLine->unit_time*$quoteLine->qty ;
                if(array_key_exists($TechnicalCutLine->label, $tableauService)){
                    $tableauService[$TechnicalCutLine->label][1] += $TotalServiceProductTimeForQuoteCurentLine;
                }
                else{
                    $tableauService[$TechnicalCutLine->label] = array($TechnicalCutLine->label, $TotalServiceProductTimeForQuoteCurentLine, $TechnicalCutLine->service['color']);
                }
            }

        }
        asort($tableauService);
        return $tableauService;
    }

    public function getTotalSettingTimeByService()
    {
        $tableauService = array();
        $quoteLines = $this->quote->quoteLines;
        foreach ($quoteLines as $quoteLine) {
            foreach ($quoteLine->TechnicalCut as $TechnicalCutLine) {
                $TotalServiceSettingTimeForQuoteCurentLine =  $TechnicalCutLine->seting_time ;
                if(array_key_exists($TechnicalCutLine->label, $tableauService)){
                    $tableauService[$TechnicalCutLine->label][1] += $TotalServiceSettingTimeForQuoteCurentLine;
                }
                else{
                    $tableauService[$TechnicalCutLine->label] = array($TechnicalCutLine->label, $TotalServiceSettingTimeForQuoteCurentLine, $TechnicalCutLine->service['color']);
                }
            }

        }
        asort($tableauService);
        return $tableauService;
    }

    public function getTotalCostByService()
    {
        $tableauService = array();
        $quoteLines = $this->quote->quoteLines;
        foreach ($quoteLines as $quoteLine) {
            foreach ($quoteLine->TechnicalCut as $TechnicalCutLine) {
                $TotalServiceCostForQuoteCurentLine =  $TechnicalCutLine->unit_cost*$quoteLine->qty ;
                if(array_key_exists($TechnicalCutLine->label, $tableauService)){
                    $tableauService[$TechnicalCutLine->label][1] += $TotalServiceCostForQuoteCurentLine;
                }
                else{
                    $tableauService[$TechnicalCutLine->label] = array($TechnicalCutLine->label, $TotalServiceCostForQuoteCurentLine, $TechnicalCutLine->service['color']);
                }
            }

        }
        asort($tableauService);
        return $tableauService;
    }

    public function getTotalPriceByService()
    {
        $tableauService = array();
        $quoteLines = $this->quote->quoteLines;
        foreach ($quoteLines as $quoteLine) {
            foreach ($quoteLine->TechnicalCut as $TechnicalCutLine) {
                $TotalServicePriceForQuoteCurentLine =  $TechnicalCutLine->unit_price*$quoteLine->qty ;
                if(array_key_exists($TechnicalCutLine->label, $tableauService)){
                    $tableauService[$TechnicalCutLine->label][1] += $TotalServicePriceForQuoteCurentLine;
                }
                else{
                    $tableauService[$TechnicalCutLine->label] = array($TechnicalCutLine->label, $TotalServicePriceForQuoteCurentLine, $TechnicalCutLine->service['color']);
                }
            }

        }
        asort($tableauService);
        return $tableauService;
    }
}