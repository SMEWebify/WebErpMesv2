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
use App\Services\PurchaseCalculator;

class PrintController extends Controller
{
     /**
     * @param $Factory
     * @return string
     */
    public function getImageFactoryPath($Factory){
        // Example image is located at `public/images/factory`
        if($Factory->picture){
            return base64_encode(file_get_contents(public_path('images\factory\\'.$Factory->picture)));
        }
        else{
            return null;
        }
    }

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
        $image= $this->getImageFactoryPath($Factory);
        $pdf = PDF::loadView('print/pdf-sales', compact('typeDocumentName','Document', 'Factory','totalPrices','subPrice','vatPrice','image'));
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
        $image= $this->getImageFactoryPath($Factory);
        $pdf = PDF::loadView('print/pdf-sales', compact('typeDocumentName','Document', 'Factory','totalPrices','subPrice','vatPrice','image'));
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
    public function getOrderConfirmPdf(Orders $Document)
    {
        $typeDocumentName = 'Order Confirm';
        $OrderCalculator = new OrderCalculator($Document);
        $Factory = Factory::first();
        $totalPrices = $OrderCalculator->getTotalPrice();
        $subPrice = $OrderCalculator->getSubTotal();
        $vatPrice = $OrderCalculator->getVatTotal();
        $Document->Lines = $Document->OrderLines;
        unset($Document->OrderLines);
        $image= $this->getImageFactoryPath($Factory);
        $pdf = PDF::loadView('print/pdf-sales', compact('typeDocumentName','Document', 'Factory','totalPrices','subPrice','vatPrice','image'));
        return $pdf->stream();
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
    public function getDeliveryPdf(Deliverys $Document)
    {
        $typeDocumentName = 'Delivery note';
        $Factory = Factory::first();
        $Document->Lines = $Document->DeliveryLines;
        unset($Document->DeliveryLines);
        $image= $this->getImageFactoryPath($Factory);
        $pdf = PDF::loadView('print/pdf-delivery', compact('typeDocumentName','Document', 'Factory','image'));
        return $pdf->stream();
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
    public function getInvoicePdf(Invoices $Document)
    {
        $typeDocumentName = 'Invoince';
        $InvoiceCalculator = new InvoiceCalculator($Document);
        $Factory = Factory::first();
        $totalPrices = $InvoiceCalculator->getTotalPrice();
        $subPrice = $InvoiceCalculator->getSubTotal();
        $vatPrice = $InvoiceCalculator->getVatTotal();
        $Document->Lines = $Document->invoiceLines;
        unset($Document->invoiceLines);
        $image= $this->getImageFactoryPath($Factory);
        $pdf = PDF::loadView('print/pdf-invoice', compact('typeDocumentName','Document', 'Factory','image'));
        return $pdf->stream();
    }
    
    /**
     * @param $Document
     * @return View
     */
    public function printPurchaseQuotation(PurchasesQuotation $Document)
    {
        $typeDocumentName = 'Request for price';
        $Factory = Factory::first();
        $Document->Lines = $Document->PurchaseQuotationLines;
        unset($Document->PurchaseQuotationLines);
        return view('print/print-purchases-quotation', compact('typeDocumentName','Document', 'Factory'));
    }
    
    /**
     * @param $Document
     * @return View
     */
    public function getPurchaseQuotationPdf(PurchasesQuotation $Document)
    {
        $typeDocumentName = 'Request for price';
        $Factory = Factory::first();
        $Document->Lines = $Document->PurchaseQuotationLines;
        unset($Document->PurchaseQuotationLines);
        $image= $this->getImageFactoryPath($Factory);
        $pdf = PDF::loadView('print/pdf-purchases-quotation', compact('typeDocumentName','Document', 'Factory','image'));
        return $pdf->stream();
    }
    

    /**
     * @param $Document
     * @return View
     */
    public function printPurchase(Purchases $Document)
    {
        $typeDocumentName = 'Purchase order';
        $PurchaseCalculator = new PurchaseCalculator($Document);
        $Factory = Factory::first();
        $totalPrices = $PurchaseCalculator->getTotalPrice();
        $Document->Lines = $Document->PurchaseLines;
        unset($Document->PurchaseLines);
        return view('print/print-purchases', compact('typeDocumentName','Document', 'Factory','totalPrices'));
    }

    /**
     * @param $Document
     * @return View
     */
    public function getPurchasePdf(Purchases $Document)
    {
        $typeDocumentName = 'Purchase order';
        $PurchaseCalculator = new PurchaseCalculator($Document);
        $Factory = Factory::first();
        $totalPrices = $PurchaseCalculator->getTotalPrice();
        $Document->Lines = $Document->PurchaseLines;
        unset($Document->PurchaseLines);
        $image= $this->getImageFactoryPath($Factory);
        $pdf = PDF::loadView('print/pdf-purchases', compact('typeDocumentName','Document', 'Factory','totalPrices','image'));
        return $pdf->stream();
    }
    
    /**
     * @param $Document
     * @return View
     */
    public function printReceipt(PurchaseReceipt $Document)
    {
        $typeDocumentName = 'Purchase Receipt';
        $Factory = Factory::first();
        $Document->Lines = $Document->PurchaseReceiptLines;
        unset($Document->PurchaseReceiptLines);
        return view('print/print-purchases-reciept', compact('typeDocumentName','Document', 'Factory'));
    }

        /**
     * @param $Document
     * @return View
     */
    public function getReceiptPdf(PurchaseReceipt $Document)
    {
        $typeDocumentName = 'Purchase Receipt';
        $Factory = Factory::first();
        $Document->Lines = $Document->PurchaseReceiptLines;
        unset($Document->PurchaseReceiptLines);
        $image= $this->getImageFactoryPath($Factory);
        $pdf = PDF::loadView('print/pdf-purchases-reciept', compact('typeDocumentName','Document', 'Factory','image'));
        return $pdf->stream();
    }
    
}
