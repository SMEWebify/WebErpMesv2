<?php
namespace App\Services;

use App\Models\Accounting\AccountingVat;
use App\Models\Workflow\Quotes;

class QuoteCalculatorService
{
    private $quote;
    private $defaultVat;

    public $TotalPrice;
    public $SubTotal;
    public $VatTotal;

    public function __construct(Quotes $quote)
    {
        $this->quote = $quote;
        $this->defaultVat = AccountingVat::getDefault();
    }

    /**
     * Calculate the total VAT for all quote lines.
     *
     * This function iterates through all quote lines, calculates the VAT for each line,
     * and aggregates the VAT amounts by their respective accounting VAT IDs.
     *
     * @return array An associative array where the keys are accounting VAT IDs and the values are arrays
     *               containing the VAT rate and the total VAT amount for that rate.
     */
    public function getVatTotal()
    {
        $tableauTVA = array();
        $quoteLines = $this->quote->quoteLines;
        foreach ($quoteLines as $quoteLine) {
            $vat = $quoteLine->VAT ?? $this->defaultVat;
            $vatRate = $vat->rate ?? 0;
            $vatId = $quoteLine->accounting_vats_id ?? $vat?->id;
            $TotalCurentLine = ($quoteLine->qty*$quoteLine->selling_price)-($quoteLine->qty*$quoteLine->selling_price)*($quoteLine->discount/100);
			$TotalVATCurentLine = $TotalCurentLine * ($vatRate / 100);
            if(array_key_exists($vatId, $tableauTVA)){
                $tableauTVA[$vatId][1] += $TotalVATCurentLine;
            }
            else{
                $tableauTVA[$vatId] = array($vatRate, $TotalVATCurentLine);
            }
        }
        asort($tableauTVA);
        return $tableauTVA;
    }

    /**
     * Calculate the total price of the quote including VAT and discounts.
     *
     * This method iterates through each quote line, calculates the line total
     * by applying the discount and VAT, and sums up the total price.
     *
     * @return float The total price of the quote.
     */
    public function getTotalPrice()
    {
        $TotalPrice = 0;
        $quoteLines = $this->quote->quoteLines;
        foreach ($quoteLines as $quoteLine) {
            $vatRate = ($quoteLine->VAT ?? $this->defaultVat)?->rate ?? 0;
            $TotalPriceLine = ($quoteLine->qty * $quoteLine->selling_price)-($quoteLine->qty * $quoteLine->selling_price)*($quoteLine->discount/100);
            $TotalVATPrice = $TotalPriceLine * ($vatRate / 100);
            $TotalPrice += $TotalPriceLine+$TotalVATPrice;
        }
        return $TotalPrice;
    }

    /**
     * Calculate the subtotal for the quote.
     *
     * This method iterates through all quote lines associated with the quote
     * and calculates the subtotal by summing up the total price for each line
     * item. The total price for each line item is calculated by multiplying
     * the quantity by the selling price and then applying any discount.
     *
     * @return float The calculated subtotal for the quote.
     */
    public function getSubTotal()
    {
        $SubTotal = 0;
        $quoteLines = $this->quote->quoteLines;
        foreach ($quoteLines as $quoteLine) {
            $SubTotal += ($quoteLine->qty * $quoteLine->selling_price)-($quoteLine->qty * $quoteLine->selling_price)*($quoteLine->discount/100);
        }
        return $SubTotal;
    }
    
    /**
     * Calculate the total product time by service for the current quote.
     *
     * This method iterates through the quote lines and their associated technical cuts,
     * calculating the total service product time for each service label. The result is
     * an associative array where the keys are the service labels and the values are arrays
     * containing the service label, the total product time, and the service color.
     *
     * @return array An associative array with service labels as keys and arrays containing
     *               the service label, total product time, and service color as values.
     */
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

    /**
     * Calculate the total setting time for each service in the quote.
     *
     * This method iterates through the quote lines and their associated technical cuts,
     * summing up the setting time for each service. The result is an associative array
     * where the keys are the service labels and the values are arrays containing the
     * service label, total setting time, and service color.
     *
     * @return array An associative array with service labels as keys and arrays containing
     *               the service label, total setting time, and service color as values.
     */
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

    /**
     * Calculate the total cost by service for the current quote.
     *
     * This method iterates through the quote lines and their associated technical cuts,
     * calculating the total cost for each service based on the unit cost and quantity.
     * It then aggregates these costs by service label and returns an array of services
     * with their respective total costs and colors.
     *
     * @return array An associative array where the keys are service labels and the values
     *               are arrays containing the service label, total cost, and service color.
     */
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

    /**
     * Calculate the total price by service for the current quote.
     *
     * This method iterates through the quote lines and their associated technical cuts,
     * calculating the total price for each service based on the unit price and quantity.
     * It then aggregates these totals into an array, indexed by the service label.
     *
     * @return array An associative array where the keys are service labels and the values are arrays containing:
     *               - The service label
     *               - The total price for the service
     *               - The service color
     */
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