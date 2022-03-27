<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Admin\Factory;
use App\Models\Purchases\PurchaseReceipt;
use App\Models\Purchases\Purchases;
use App\Models\Purchases\PurchasesQuotation;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use App\Models\Workflow\Invoices;
use App\Services\OrderCalculator;
use App\Services\QuoteCalculator;
use App\Models\Workflow\Deliverys;
use App\Services\InvoiceCalculator;

class PrintController extends Controller
{
    public function printQuote(Quotes $id)
    {
        $QuoteCalculator = new QuoteCalculator($id);
        $Factory = Factory::first();
        $totalPrice = $QuoteCalculator->getTotalPrice();
        $subPrice = $QuoteCalculator->getSubTotal();
        $vatPrice = $QuoteCalculator->getVatTotal();
        $id->Lines = $id->QuoteLines;
        unset($id->QuoteLines);
        return view('print/print-sales', [
            'typeDocumentName' => 'Quote',
            'Document' => $id,
            'Factory' => $Factory,
            'totalPrices' => $totalPrice,
            'subPrice' => $subPrice, 
            'vatPrice' => $vatPrice,
        ]);
    }
    
    public function printOrder(Orders $id)
    {
        $OrderCalculator = new OrderCalculator($id);
        $Factory = Factory::first();
        $totalPrice = $OrderCalculator->getTotalPrice();
        $subPrice = $OrderCalculator->getSubTotal();
        $vatPrice = $OrderCalculator->getVatTotal();
        $id->Lines = $id->OrderLines;
        unset($id->OrderLines);
        return view('print/print-sales', [
            'typeDocumentName' => 'Order',
            'Document' => $id,
            'Factory' => $Factory,
            'totalPrices' => $totalPrice,
            'subPrice' => $subPrice, 
            'vatPrice' => $vatPrice,
        ]);
    }

    public function printOrderConfirm(Orders $id)
    {
        $OrderCalculator = new OrderCalculator($id);
        $Factory = Factory::first();
        $totalPrice = $OrderCalculator->getTotalPrice();
        $subPrice = $OrderCalculator->getSubTotal();
        $vatPrice = $OrderCalculator->getVatTotal();
        $id->Lines = $id->OrderLines;
        unset($id->OrderLines);
        return view('print/print-sales', [
            'typeDocumentName' => 'Order confirm',
            'Document' => $id,
            'Factory' => $Factory,
            'totalPrices' => $totalPrice,
            'subPrice' => $subPrice, 
            'vatPrice' => $vatPrice,
        ]);
    }

    public function printOrderManufacturingInstruction(Orders $id)
    {
        $Factory = Factory::first();
        $id->Lines = $id->OrderLines;
        unset($id->OrderLines);
        return view('print/print-manufacturing-instruction', [
            'typeDocumentName' => 'Order anufacturing Instruction',
            'Document' => $id,
            'Factory' => $Factory,
        ]);
    }
    
    public function printDelivery(Deliverys $id)
    {
        $Factory = Factory::first();
        $id->Lines = $id->DeliveryLines;
        unset($id->DeliveryLines);
        return view('print/print-delivery', [
            'typeDocumentName' => 'Delivery note',
            'Document' => $id,
            'Factory' => $Factory,
        ]);
    }

    public function printInvoince(Invoices $id)
    {
        $InvoiceCalculator = new InvoiceCalculator($id);
        $Factory = Factory::first();
        $totalPrice = $InvoiceCalculator->getTotalPrice();
        $subPrice = $InvoiceCalculator->getSubTotal();
        $vatPrice = $InvoiceCalculator->getVatTotal();
        $id->Lines = $id->invoiceLines;
        unset($id->invoiceLines);
        return view('print/print-sales', [
            'typeDocumentName' => 'Invoince',
            'Document' => $id,
            'Factory' => $Factory,
            'totalPrices' => $totalPrice,
            'subPrice' => $subPrice, 
            'vatPrice' => $vatPrice,
        ]);
    }

    public function printPurchaseQuotation(PurchasesQuotation $id)
    {
        $Factory = Factory::first();
        return view('print/print', [
            'Document' => $id,
            'Factory' => $Factory,
        ]);
    }

    public function printPurchase(Purchases $id)
    {
        $Factory = Factory::first();
        return view('print/print', [
            'Document' => $id,
            'Factory' => $Factory,
        ]);
    }

    public function printReceipt(PurchaseReceipt $id)
    {
        $Factory = Factory::first();
        return view('print/print', [
            'Document' => $id,
            'Factory' => $Factory,
        ]);
    }
    
}
