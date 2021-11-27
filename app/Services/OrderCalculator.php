<?php
namespace App\Services;

use App\Models\Workflow\Orders;
use App\Repositories\Money;
use App\Repositories\Tax;

class OrderCalculator
{
    /**
     * @var Order
     */
    private $order;

    public $TotalPrice;
    public $SubTotal;
    public $VatTotal;

    public function __construct(Orders $order)
    {
        $this->order = $order;
    }

    public function getVatTotal()
    {
        $tableauTVA = array();
        $orderLines = $this->order->orderLines;
        foreach ($orderLines as $orderLine) {
            $TotalCurentLine = ($orderLine->qty*$orderLine->selling_price)-($orderLine->qty*$orderLine->selling_price)*($orderLine->discount/100);
			$TotalVATCurentLine =  $TotalCurentLine*($orderLine->VAT['RATE']/100) ;
            if(array_key_exists($orderLine->accounting_vats_id, $tableauTVA)){
                $tableauTVA[$orderLine->accounting_vats_id][1] += $TotalVATCurentLine;
            }
            else{
                $tableauTVA[$orderLine->accounting_vats_id] = array($orderLine->VAT['RATE'], $TotalVATCurentLine);
            }
        }
        asort($tableauTVA);
        return $tableauTVA;
    }


    public function getTotalPrice()
    {
        $TotalPrice = 0;
        $orderLines = $this->order->orderLines;

        foreach ($orderLines as $orderLine) {
            $TotalPriceLine = ($orderLine->qty * $orderLine->selling_price)-($orderLine->qty * $orderLine->selling_price)*($orderLine->discount/100);
            $TotalVATPrice = $TotalPriceLine*($orderLine->VAT['RATE']/100);
            $TotalPrice += $TotalPriceLine+$TotalVATPrice;
        }
        return $TotalPrice;
    }

    public function getSubTotal()
    {
        $SubTotal = 0;
        $orderLines = $this->order->orderLines;
        foreach ($orderLines as $orderLine) {
            $SubTotal += ($orderLine->qty * $orderLine->selling_price)-($orderLine->qty * $orderLine->selling_price)*($orderLine->discount/100);
        }
        return $SubTotal;
    }

}