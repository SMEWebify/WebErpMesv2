<?php

namespace App\Http\Controllers;


use App\Models\Admin\Factory;

use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use PDF;
use App\Models\Workflow\Invoices;
use App\Services\OrderCalculator;
use App\Services\QuoteCalculator;
use App\Models\Workflow\Deliverys;
use App\Models\Purchases\Purchases;
use App\Services\InvoiceCalculator;
use App\Models\Purchases\PurchaseReceipt;
use App\Models\Purchases\PurchasesQuotation;

class PrintController extends Controller
{
    /**
     * @param $Document
     * @return View
     */
    public function printQuote(Quotes $Document)
    {
        $typeDocumentName = 'Quote';
        $QuoteCalculator = new QuoteCalculator($Document);
        $Factory = Factory::first();
        $totalPrices = $QuoteCalculator->getTotalPrice();
        $subPrice = $QuoteCalculator->getSubTotal();
        $vatPrice = $QuoteCalculator->getVatTotal();
        $Document->Lines = $Document->QuoteLines;
        unset($Document->QuoteLines);
        return view('print/print-sales', compact('typeDocumentName','Document', 'Factory','totalPrices','subPrice','vatPrice'));
    }

    /**
     * @param $Document
     * @return View
     */
    public function getQuotePdf(Quotes $Document)
    {
        $typeDocumentName = 'Quote';
        $QuoteCalculator = new QuoteCalculator($Document);
        $Factory = Factory::first();
        $totalPrices = $QuoteCalculator->getTotalPrice();
        $subPrice = $QuoteCalculator->getSubTotal();
        $vatPrice = $QuoteCalculator->getVatTotal();
        $Document->Lines = $Document->QuoteLines;
        unset($Document->QuoteLines);

        $pdf = PDF::loadView('print/pdf-sales', compact('typeDocumentName','Document', 'Factory','totalPrices','subPrice','vatPrice'));
        return $pdf->stream();
    }
    
    /**
     * @param $Document
     * @return View
     */
    public function printOrder(Orders $Document)
    {
        $typeDocumentName = 'Order';
        $OrderCalculator = new OrderCalculator($Document);
        $Factory = Factory::first();
        $totalPrices = $OrderCalculator->getTotalPrice();
        $subPrice = $OrderCalculator->getSubTotal();
        $vatPrice = $OrderCalculator->getVatTotal();
        $Document->Lines = $Document->OrderLines;
        unset($Document->OrderLines);
        return view('print/print-sales', compact('typeDocumentName','Document', 'Factory','totalPrices','subPrice','vatPrice'));
    }
    
    /**
     * @param $Document
     * @return View
     */
    public function getOrderPdf(Orders $Document)
    {
        $typeDocumentName = 'Order';
        $OrderCalculator = new OrderCalculator($Document);
        $Factory = Factory::first();
        $totalPrices = $OrderCalculator->getTotalPrice();
        $subPrice = $OrderCalculator->getSubTotal();
        $vatPrice = $OrderCalculator->getVatTotal();
        $Document->Lines = $Document->OrderLines;
        unset($Document->OrderLines);

        $pdf = PDF::loadView('print/pdf-sales', compact('typeDocumentName','Document', 'Factory','totalPrices','subPrice','vatPrice'));
        return $pdf->stream();
    }
    
    /**
     * @param $Document
     * @return View
     */
    public function printOrderConfirm(Orders $Document)
    {
        $typeDocumentName = 'Order confirm';
        $OrderCalculator = new OrderCalculator($Document);
        $Factory = Factory::first();
        $totalPrices = $OrderCalculator->getTotalPrice();
        $subPrice = $OrderCalculator->getSubTotal();
        $vatPrice = $OrderCalculator->getVatTotal();
        $Document->Lines = $Document->OrderLines;
        unset($Document->OrderLines);
        return view('print/print-sales', compact('typeDocumentName','Document', 'Factory','totalPrices','subPrice','vatPrice'));
    }
    
    /**
     * @param $Document
     * @return View
     */
    public function printOrderManufacturingInstruction(Orders $Document)
    {
        $typeDocumentName = 'Order anufacturing Instruction';
        $Factory = Factory::first();
        $Document->Lines = $Document->OrderLines;
        unset($Document->OrderLines);
        return view('print/print-manufacturing-instruction', compact('typeDocumentName','Document', 'Factory'));
    }
    
    /**
     * @param $Document
     * @return View
     */
    public function printDelivery(Deliverys $Document)
    {
        $typeDocumentName = 'Delivery note';
        $Factory = Factory::first();
        $Document->Lines = $Document->DeliveryLines;
        unset($Document->DeliveryLines);
        return view('print/print-delivery', compact('typeDocumentName','Document', 'Factory'));
    }
    
    /**
     * @param $Document
     * @return View
     */
    public function printInvoince(Invoices $Document)
    {
        $typeDocumentName = 'Invoince';
        $InvoiceCalculator = new InvoiceCalculator($Document);
        $Factory = Factory::first();
        $totalPrices = $InvoiceCalculator->getTotalPrice();
        $subPrice = $InvoiceCalculator->getSubTotal();
        $vatPrice = $InvoiceCalculator->getVatTotal();
        $Document->Lines = $Document->invoiceLines;
        unset($Document->invoiceLines);
        return view('print/print-invoice', compact('typeDocumentName','Document', 'Factory','totalPrices','subPrice','vatPrice'));
    }
    
    /**
     * @param $Document
     * @return View
     */
    public function printPurchaseQuotation(PurchasesQuotation $Document)
    {
        $Factory = Factory::first();
        return view('print/print', [
            'Document' => $Document,
            'Factory' => $Factory,
        ]);
    }
    
    /**
     * @param $Document
     * @return View
     */
    public function printPurchase(Purchases $Document)
    {
        $Factory = Factory::first();
        return view('print/print', [
            'Document' => $Document,
            'Factory' => $Factory,
        ]);
    }
    
    /**
     * @param $Document
     * @return View
     */
    public function printReceipt(PurchaseReceipt $Document)
    {
        $Factory = Factory::first();
        return view('print/print', [
            'Document' => $Document,
            'Factory' => $Factory,
        ]);
    }
    
}
