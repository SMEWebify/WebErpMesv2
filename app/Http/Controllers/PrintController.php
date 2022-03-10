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
        return view('print/print', [
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
        return view('print/print', [
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
        return view('print/print', [
            'typeDocumentName' => 'Order confirm',
            'Document' => $id,
            'Factory' => $Factory,
            'totalPrices' => $totalPrice,
            'subPrice' => $subPrice, 
            'vatPrice' => $vatPrice,
        ]);
    }

    public function printDelivery(Deliverys $id)
    {
        $Factory = Factory::first();
        return view('print/print', [
            'Document' => $id,
            'Factory' => $Factory,
        ]);
    }

    public function printInvoince(Invoices $id)
    {
        $Factory = Factory::first();
        return view('print/print', [
            'Document' => $id,
            'Factory' => $Factory,
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
