<?php

namespace App\Services;

use Illuminate\Support\Number;

class OrderInvoiceDataService
{
    /**
     * Calculate the total invoiced amount for a given order.
     *
     * This function iterates through the order lines and sums up the total amount
     * invoiced by multiplying the quantity of each invoice line by the selling price
     * of the corresponding order line.
     *
     * @param \App\Models\Workflow\Orders $order The order object containing order lines and their invoice lines.
     * @return float The total invoiced amount for the given order.
     */
    public function getInvoicingAmount($order)
    {
        // Amount invoiced (total invoices sent)
        $invoicedAmount = $order->orderLines->sum(function ($line) {
            // Check that the 'invoiceLines' relation is well defined
            return $line->invoiceLines->sum(function ($invoiceLine) use ($line) {
                return $invoiceLine->qty * $line->selling_price;
            });
        });
        
        return $invoicedAmount;
    }

    /**
     * Calculate the total received payment for invoicing based on the given order.
     *
     * This function iterates through the order lines and their corresponding invoice lines
     * to sum up the payments received for invoiced items. Only invoice lines with a status
     * of 5 (indicating that the invoice is paid) are considered in the calculation.
     *
     * @param \App\Models\Workflow\Orders $order The order object containing order lines and invoice lines.
     * @return float The total received payment for the invoiced items.
     */
    public function getInvoicingReceivedPayment($order)
    {
        
        $factory = app('Factory'); 
        $currency = $factory->curency ?? 'EUR';
        
        $receivedPayment = $order->orderLines->sum(function ($line) {
            return $line->invoiceLines->sum(function ($invoiceLine) use ($line) {
                // Check that the invoice line is paid (invoice status = 5)
                if ($invoiceLine->invoice_status == 5) {
                    return $invoiceLine->qty * $line->selling_price;
                }
                return 0;
            });
        });
        
        return Number::currency($receivedPayment, $currency, config('app.locale'));;
    }
}
