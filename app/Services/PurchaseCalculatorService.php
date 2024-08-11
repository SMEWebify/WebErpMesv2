<?php
namespace App\Services;

use App\Models\Purchases\Purchases;
use App\Repositories\Money;
use App\Repositories\Tax;

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

    public function getVatTotal()
    {
        $tableauTVA = array();
        $purchaseLines = $this->purchase->purchaseLines;
        foreach ($purchaseLines as $purchaseLine) {
            $VAT =  0;
            if($purchaseLine->accounting_vats_id){
                $VAT =  $purchaseLine->VAT['rate'];
            }
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

    public function getTotalPrice()
    {
        $TotalPrice = 0;
        $purchaseLines = $this->purchase->purchaseLines;

        foreach ($purchaseLines as $purchaseLine) {
            
            $VAT =  0;
            if($purchaseLine->accounting_vats_id){
                $VAT =  $purchaseLine->VAT['rate'];
            }
            $TotalPriceLine = ($purchaseLine->qty * $purchaseLine->selling_price)-($purchaseLine->qty * $purchaseLine->selling_price)*($purchaseLine->discount/100);
            $TotalVATPrice = $TotalPriceLine*($VAT/100);
            $TotalPrice += $TotalPriceLine+$TotalVATPrice;
        }
        return $TotalPrice;
    }

    public function getSubTotal()
    {
        $SubTotal = 0;
        $purchaseLines = $this->purchase->purchaseLines;
        foreach ($purchaseLines as $purchaseLine) {
            $SubTotal += ($purchaseLine->qty * $purchaseLine->selling_price)-($purchaseLine->qty * $purchaseLine->selling_price)*($purchaseLine->discount/100);
        }
        return $SubTotal;
    } 
}