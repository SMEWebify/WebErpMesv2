<?php
namespace App\Services;

use App\Models\Workflow\Orders;

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

    /**
     * Calculate the total VAT for the order.
     *
     * This function iterates through the order lines, calculates the VAT for each line,
     * and aggregates the VAT amounts by their accounting VAT ID. The result is an array
     * where the keys are the accounting VAT IDs and the values are arrays containing the
     * VAT rate and the total VAT amount for that rate.
     *
     * @return array An associative array where the keys are accounting VAT IDs and the values
     *               are arrays with the VAT rate and the total VAT amount.
     */
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

    /**
     * Calculate the total price of the order including discounts and VAT.
     *
     * This method iterates through each order line, calculates the line total 
     * by applying the discount, then adds the VAT to get the final line total.
     * It sums up all the line totals to get the overall total price of the order.
     *
     * @return float The total price of the order including discounts and VAT.
     */
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

    /**
     * Calculate the subtotal for the order.
     *
     * This method iterates through each order line, calculates the line total by multiplying
     * the quantity by the selling price, applies any discount, and sums up the results to get
     * the subtotal for the entire order.
     *
     * @return float The subtotal amount for the order.
     */
    public function getSubTotal()
    {
        $SubTotal = 0;
        $orderLines = $this->order->orderLines;
        foreach ($orderLines as $orderLine) {
            $SubTotal += ($orderLine->qty * $orderLine->selling_price)-($orderLine->qty * $orderLine->selling_price)*($orderLine->discount/100);
        }
        return $SubTotal;
    } 

    /**
     * Calculate the total product time by service for the current order.
     *
     * This function iterates through the order lines and their associated technical cuts,
     * calculating the total service product time for each service label. The results are
     * stored in an associative array where the key is the service label and the value is
     * an array containing the service label, total time, and service color.
     *
     * @return array An associative array where the key is the service label and the value
     *               is an array containing the service label, total time, and service color.
     */
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

    /**
     * Calculate the total setting time by service for the current order.
     *
     * This method iterates through the order lines and their associated technical cuts,
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

    /**
     * Calculate the total cost by service for the current order.
     *
     * This method iterates through the order lines and their associated technical cuts,
     * calculating the total cost for each service based on the unit cost and quantity.
     * It then aggregates these costs by service label and returns an array of services
     * with their total costs and associated colors.
     *
     * @return array An associative array where the keys are service labels and the values
     *               are arrays containing the service label, total cost, and service color.
     */
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

    /**
     * Calculate the total price by service for the current order.
     *
     * This method iterates through the order lines and their associated technical cuts,
     * calculating the total price for each service based on the unit price and quantity.
     * It then aggregates these totals by service label and returns an array of services
     * with their corresponding total prices and colors.
     *
     * @return array An associative array where the keys are service labels and the values
     *               are arrays containing the service label, total price, and service color.
     */
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