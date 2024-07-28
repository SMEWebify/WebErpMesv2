<?php
namespace App\Services;

use App\Models\Workflow\Orders;
use App\Repositories\Money;
use App\Repositories\Tax;

class OrderCalculatorService
{
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
			$TotalVATCurentLine =  $TotalCurentLine*($orderLine->VAT['rate']/100) ;
            if(array_key_exists($orderLine->accounting_vats_id, $tableauTVA)){
                $tableauTVA[$orderLine->accounting_vats_id][1] += $TotalVATCurentLine;
            }
            else{
                $tableauTVA[$orderLine->accounting_vats_id] = array($orderLine->VAT['rate'], $TotalVATCurentLine);
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
            $TotalVATPrice = $TotalPriceLine*($orderLine->VAT['rate']/100);
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

    public function getTotalProductTimeByService()
    {
        $tableauService = array();
        $orderLines = $this->order->orderLines;
        foreach ($orderLines as $orderLine) {
            foreach ($orderLine->TechnicalCut as $TechnicalCutLine) {
                $TotalServiceProductTimeForQuoteCurentLine =  $TechnicalCutLine->unit_time*$orderLine->qty ;
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
        $orderLines = $this->order->orderLines;
        foreach ($orderLines as $orderLine) {
            foreach ($orderLine->TechnicalCut as $TechnicalCutLine) {
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
        $orderLines = $this->order->orderLines;
        foreach ($orderLines as $orderLine) {
            foreach ($orderLine->TechnicalCut as $TechnicalCutLine) {
                $TotalServiceCostForQuoteCurentLine =  $TechnicalCutLine->unit_cost*$orderLine->qty ;
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
        $orderLines = $this->order->orderLines;
        foreach ($orderLines as $orderLine) {
            foreach ($orderLine->TechnicalCut as $TechnicalCutLine) {
                $TotalServicePriceForQuoteCurentLine =  $TechnicalCutLine->unit_price*$orderLine->qty ;
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