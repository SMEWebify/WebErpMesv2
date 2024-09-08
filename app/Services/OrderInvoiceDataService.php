<?php

namespace App\Services;

class OrderInvoiceDataService
{
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

    public function getInvoicingReceivedPayment($order)
    {
        $receivedPayment = $order->orderLines->sum(function ($line) {
            return $line->invoiceLines->sum(function ($invoiceLine) use ($line) {
                // Check that the invoice line is paid (invoice status = 5)
                if ($invoiceLine->invoice_status == 5) {
                    return $invoiceLine->qty * $line->selling_price;
                }
                return 0;
            });
        });
        
        return $receivedPayment;
    }
}
