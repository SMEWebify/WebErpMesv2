<?php
namespace App\Services;

use App\Models\Purchases\Purchases;

class PurchaseCalculatorService
{
    /**
     * @var Purchases
     */
    private $purchase;

    public $TotalPrice;

    public function __construct(Purchases $purchase)
    {
        $this->purchase = $purchase;
    }

    /**
     * Calculate the total VAT for the purchase lines.
     *
     * This function iterates through the purchase lines and calculates the VAT for each line.
     * It then aggregates the VAT amounts by their respective VAT rates.
     *
     * @return array An associative array where the keys are the VAT IDs and the values are arrays containing the VAT rate and the total VAT amount for that rate.
     */
    public function getVatTotal()
    {
        $tableauTVA = array();
        $purchaseLines = $this->purchase->purchaseLines;
        foreach ($purchaseLines as $purchaseLine) {
            $VAT = optional($purchaseLine->VAT)['rate'] ?? 0;
            $TotalCurentLine = ($purchaseLine->qty*$purchaseLine->selling_price)-($purchaseLine->qty*$purchaseLine->selling_price)*($purchaseLine->discount/100);
			$TotalVATCurentLine =  $TotalCurentLine*($VAT/100) ;
            if(array_key_exists($purchaseLine->accounting_vats_id, $tableauTVA)){
                $tableauTVA[$purchaseLine->accounting_vats_id][1] += $TotalVATCurentLine;
            }
            else{
                $tableauTVA[$purchaseLine->accounting_vats_id] = array($VAT, $TotalVATCurentLine);
            }
        }
        asort($tableauTVA);
        return $tableauTVA;
    }

    /**
     * Calculate the total price of all purchase lines including VAT and discounts.
     *
     * This method iterates through each purchase line, calculates the line total 
     * price by considering the quantity, selling price, and discount. It also 
     * calculates the VAT for each line if applicable and adds it to the line total.
     * The sum of all line totals including VAT is returned as the total price.
     *
     * @return float The total price of all purchase lines including VAT and discounts.
     */
    public function getTotalPrice()
    {
        $TotalPrice = 0;
        $purchaseLines = $this->purchase->purchaseLines;

        foreach ($purchaseLines as $purchaseLine) {
            
            $VAT = optional($purchaseLine->VAT)['rate'] ?? 0;
            $TotalPriceLine = ($purchaseLine->qty * $purchaseLine->selling_price)-($purchaseLine->qty * $purchaseLine->selling_price)*($purchaseLine->discount/100);
            $TotalVATPrice = $TotalPriceLine*($VAT/100);
            $TotalPrice += $TotalPriceLine+$TotalVATPrice;
        }
        return $TotalPrice;
    }

    /**
     * Calculate the subtotal for the purchase.
     *
     * This method iterates through all purchase lines associated with the purchase
     * and calculates the subtotal by summing up the product of quantity and selling price
     * for each purchase line, adjusted for any discounts.
     *
     * @return float The calculated subtotal for the purchase.
     */
    public function getSubTotal()
    {
        $SubTotal = 0;
        $purchaseLines = $this->purchase->purchaseLines;
        foreach ($purchaseLines as $purchaseLine) {
            $SubTotal += round($purchaseLine->qty * $purchaseLine->selling_price * (1 - $purchaseLine->discount / 100), 2);
        }
        return $SubTotal;
    }
}