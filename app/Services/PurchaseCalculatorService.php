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

    public function getTotalPrice()
    {
        $TotalPrice = 0;
        $purchaseLines = $this->purchase->purchaseLines;

        foreach ($purchaseLines as $purchaseLine) {
            $TotalPrice = ($purchaseLine->qty * $purchaseLine->selling_price)-($purchaseLine->qty * $purchaseLine->selling_price)*($purchaseLine->discount/100);
        }
        return $TotalPrice;
    }


}