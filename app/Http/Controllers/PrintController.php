<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf as PDF;
use App\Models\Admin\Factory;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\Deliverys;
use App\Models\Purchases\Purchases;
use App\Models\Workflow\CreditNotes;
use horstoeko\zugferd\ZugferdProfiles;
use App\Services\OrderCalculatorService;
use App\Services\QuoteCalculatorService;
use App\Models\Purchases\PurchaseReceipt;
use App\Services\InvoiceCalculatorService;
use App\Services\PurchaseCalculatorService;
use App\Models\Purchases\PurchasesQuotation;
use App\Models\Quality\QualityNonConformity;
use App\Services\CreditNoteCalculatorService;
use horstoeko\zugferd\ZugferdDocumentBuilder;
use horstoeko\zugferd\ZugferdDocumentPdfBuilder;
use horstoeko\zugferd\codelists\ZugferdPaymentMeans;
use Webklex\PDFMerger\Facades\PDFMerger;

class PrintController extends Controller
{
    /**
     * @param Quotes $Document
     * @return \Illuminate\Contracts\View\View
     */
    public function getQuotePdf(Quotes $Document)
    {
        $typeDocumentName = __('general_content.quote_trans_key');
        $calculatorService = new QuoteCalculatorService($Document);
        return $this->generatePdf($Document, $typeDocumentName, $calculatorService, 'print/pdf-sales');
    }

    /**
     * @param Orders $Document
     * @return \Illuminate\Contracts\View\View
     */
    public function getOrderPdf(Orders $Document)
    {
        $typeDocumentName = __('general_content.order_trans_key');
        $calculatorService = new OrderCalculatorService($Document);
        return $this->generatePdf($Document, $typeDocumentName, $calculatorService, 'print/pdf-sales');
    }

    /**
     * @param Orders $Document
     * @return \Illuminate\Contracts\View\View
     */
    public function getOrderConfirmPdf(Orders $Document)
    {
        $typeDocumentName = __('general_content.order_confirm_trans_key');
        $calculatorService = new OrderCalculatorService($Document);
        return $this->generatePdf($Document, $typeDocumentName, $calculatorService, 'print/pdf-sales');
    }

    /**
     * @param Orders $Document
     * @return \Illuminate\Contracts\View\View
     */
    public function printOrderManufacturingInstruction(Orders $Document)
    {
        $typeDocumentName = 'Order Manufacturing Instruction';
        $Factory = $this->getFactory();
        $this->getDocumentLines($Document, 'OrderLines');
        return view('print/print-manufacturing-instruction', compact('typeDocumentName', 'Document', 'Factory'));
    }

    /**
     * @param Deliverys $Document
     * @return \Illuminate\Contracts\View\View
     */
    public function getDeliveryPdf(Deliverys $Document)
    {
        $typeDocumentName = __('general_content.delivery_notes_trans_key');
        return $this->generatePdf($Document, $typeDocumentName, null, 'print/pdf-delivery');
    }

    /**
     * @param Invoices $Document
     * @return \Illuminate\Contracts\View\View
     */
    public function getInvoicePdf(Invoices $Document)
    {
        $typeDocumentName = __('general_content.invoice_trans_key');
        $calculatorService = new InvoiceCalculatorService($Document);
        return $this->generatePdf($Document, $typeDocumentName, $calculatorService, 'print/pdf-invoice');
    }

    /**
     * @param Invoices $Document
     * @return \Illuminate\Contracts\View\View
     */
    public function getInvoiceFactureX(Invoices $Document)
    {
        $typeDocumentName = __('general_content.invoice_trans_key');
        $calculatorService = new InvoiceCalculatorService($Document);
        $Factory = $this->getFactory();
        $totalPrices = $calculatorService->getTotalPrice();
        $subPrice = $calculatorService->getSubTotal();
        $vatPrice = $calculatorService->getVatTotal();
        $this->getDocumentLines($Document, 'invoiceLines');
        $image = $Factory->getImageFactoryPath();
        $dompdf = PDF::loadView('print/pdf-invoice', compact('typeDocumentName', 'Document', 'Factory', 'image', 'totalPrices', 'subPrice', 'vatPrice', 'image'));

        $zugferddatas = ZugferdDocumentBuilder::CreateNew(ZugferdProfiles::PROFILE_EN16931);
        $zugferddatas
            ->setDocumentInformation("471102", "380", \DateTime::createFromFormat("Ymd", "20180305"), "EUR")
            ->addDocumentNote('Rechnung gemäß Bestellung vom 01.03.2018.')
            ->addDocumentNote('Lieferant GmbH' . PHP_EOL . 'Lieferantenstraße 20' . PHP_EOL . '80333 München' . PHP_EOL . 'Deutschland' . PHP_EOL . 'Geschäftsführer: Hans Muster' . PHP_EOL . 'Handelsregisternummer: H A 123' . PHP_EOL . PHP_EOL, null, 'REG')
            ->setDocumentSupplyChainEvent(\DateTime::createFromFormat('Ymd', '20180305'))
            ->addDocumentPaymentMean(ZugferdPaymentMeans::UNTDID_4461_58, null, null, null, null, null, "DE12500105170648489890", null, null, null)
            ->setDocumentSeller("Lieferant GmbH", "549910")
            ->addDocumentSellerGlobalId("4000001123452", "0088")
            ->addDocumentSellerTaxRegistration("FC", "201/113/40209")
            ->addDocumentSellerTaxRegistration("VA", "DE123456789")
            ->setDocumentSellerAddress("Lieferantenstraße 20", "", "", "80333", "München", "DE")
            ->setDocumentSellerContact("Heinz Mükker", "Buchhaltung", "+49-111-2222222", "+49-111-3333333", "info@lieferant.de")
            ->setDocumentBuyer("Kunden AG Mitte", "GE2020211")
            ->setDocumentBuyerReference("34676-342323")
            ->setDocumentBuyerAddress("Kundenstraße 15", "", "", "69876", "Frankfurt", "DE")
            ->addDocumentTax("S", "VAT", 275.0, 19.25, 7.0)
            ->addDocumentTax("S", "VAT", 198.0, 37.62, 19.0)
            ->setDocumentSummation(529.87, 529.87, 473.00, 0.0, 0.0, 473.00, 56.87, null, 0.0)
            ->addDocumentPaymentTerm("Zahlbar innerhalb 30 Tagen netto bis 04.04.2018, 3% Skonto innerhalb 10 Tagen bis 15.03.2018")
            ->addNewPosition("1")
            ->setDocumentPositionProductDetails("Trennblätter A4", "", "TB100A4", null, "0160", "4012345001235")
            ->setDocumentPositionGrossPrice(9.9000)
            ->setDocumentPositionNetPrice(9.9000)
            ->setDocumentPositionQuantity(20, "H87")
            ->addDocumentPositionTax('S', 'VAT', 19)
            ->setDocumentPositionLineSummation(198.0);

        $zugferdpdf = new ZugferdDocumentPdfBuilder($zugferddatas, $dompdf->stream());
        $zugferdpdf->generateDocument();
        $zugferdpdf->saveDocument(public_path('pdf\invoices') . '\\' . $typeDocumentName . '-' . $Document->id . '.pdf');
        return response()->file(public_path('pdf\invoices') . '\\' . $typeDocumentName . '-' . $Document->id . '.pdf');
    }

    /**
     * @param CreditNotes $Document
     * @return \Illuminate\Contracts\View\View
     */
    public function getCreditNotePdf(CreditNotes $Document)
    {
        $typeDocumentName = __('general_content.credit_note_trans_key');
        $calculatorService = new CreditNoteCalculatorService($Document);
        return $this->generatePdf($Document, $typeDocumentName, $calculatorService, 'print/pdf-credit-note');
    }

    /**
     * @param PurchasesQuotation $Document
     * @return \Illuminate\Contracts\View\View
     */
    public function getPurchaseQuotationPdf(PurchasesQuotation $Document)
    {
        $typeDocumentName = __('general_content.purchase_request_trans_key');
        return $this->generatePdf($Document, $typeDocumentName, null, 'print/pdf-purchases-quotation');
    }

    /**
     * @param Purchases $Document
     * @return \Illuminate\Contracts\View\View
     */
    public function getPurchasePdf(Purchases $Document)
    {
        $typeDocumentName = __('general_content.purchase_order_trans_key');
        $calculatorService = new PurchaseCalculatorService($Document);
        return $this->generatePdf($Document, $typeDocumentName, $calculatorService, 'print/pdf-purchases');
    }

    /**
     * @param PurchaseReceipt $Document
     * @return \Illuminate\Contracts\View\View
     */
    public function getReceiptPdf(PurchaseReceipt $Document)
    {
        $typeDocumentName = __('general_content.po_receipt_trans_key');
        return $this->generatePdf($Document, $typeDocumentName, null, 'print/pdf-purchases-receipt');
    }

    /**
     * @param QualityNonConformity $Document
     * @return \Illuminate\Contracts\View\View
     */
    public function getNCPdf(QualityNonConformity $Document)
    {
        $typeDocumentName = __('general_content.non_conformitie_trans_key');
        return $this->generatePdf($Document, $typeDocumentName, null, 'print/pdf-nc');
    }

    /**
     * Generate PDF and stream download.
     *
     * @param $Document
     * @param string $typeDocumentName
     * @param $calculatorService
     * @param string $view
     * @return \Illuminate\Http\Response
     */
    private function generatePdf($Document, $typeDocumentName, $calculatorService, $view)
    {
        $Factory = $this->getFactory();
        $totalPrices = $calculatorService ? $calculatorService->getTotalPrice() : null;
        $subPrice = $calculatorService ? $calculatorService->getSubTotal() : null;
        $vatPrice = $calculatorService ? $calculatorService->getVatTotal() : null;
        $this->getDocumentLines($Document, $this->getDocumentLinesKey($Document));
        $image = $Factory->getImageFactoryPath();
        $pdf = PDF::loadView($view, compact('typeDocumentName', 'Document', 'Factory', 'totalPrices', 'subPrice', 'vatPrice', 'image'));

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, $Document->code . '.pdf');
    }

    /**
     * Get the factory instance.
     *
     * @return \App\Models\Admin\Factory
     */
    private function getFactory()
    {
        return Factory::first();
    }

    /**
     * Set and unset document lines.
     *
     * @param $Document
     * @param string $linesKey
     */
    private function getDocumentLines($Document, $linesKey)
    {
        $Document->Lines = $Document->$linesKey;
        unset($Document->$linesKey);
    }

    /**
     * Get the document lines key based on the document type.
     *
     * @param $Document
     * @return string
     */
    private function getDocumentLinesKey($Document)
    {
        switch (get_class($Document)) {
            case Quotes::class:
                return 'QuoteLines';
            case Orders::class:
                return 'OrderLines';
            case Invoices::class:
                return 'invoiceLines';
            case Deliverys::class:
                return 'DeliveryLines';
            case CreditNotes::class:
                return 'creditNotelines';
            case PurchasesQuotation::class:
                return 'PurchaseQuotationLines';
            case Purchases::class:
                return 'PurchaseLines';
            case PurchaseReceipt::class:
                return 'PurchaseReceiptLines';
            case QualityNonConformity::class:
                return 'QualityNonConformityLines';
            default:
                throw new \Exception('Unknown document type');
        }
    }
}