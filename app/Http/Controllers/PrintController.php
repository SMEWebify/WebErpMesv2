<?php

namespace App\Http\Controllers;

use App\Models\Admin\Factory;
use Illuminate\Support\Number;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\Deliverys;
use App\Models\Purchases\Purchases;
use App\Models\Workflow\CreditNotes;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use horstoeko\zugferd\ZugferdProfiles;
use App\Services\OrderCalculatorService;
use App\Services\QuoteCalculatorService;
use App\Models\Purchases\PurchaseReceipt;
use App\Services\InvoiceCalculatorService;
use App\Services\PurchaseCalculatorService;
use App\Models\Purchases\PurchasesQuotation;
use App\Models\Quality\QualityNonConformity;
use App\Services\CreditNoteCalculatorService;
use App\Services\PdfThemeResolver;
use horstoeko\zugferd\ZugferdDocumentBuilder;
use horstoeko\zugferd\ZugferdDocumentPdfBuilder;
use horstoeko\zugferd\codelists\ZugferdPaymentMeans;

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
        $factory = app('Factory'); 
        $currency = $factory->curency ?? 'EUR';
        $typeDocumentName = __('general_content.invoice_trans_key');
        $calculatorService = new InvoiceCalculatorService($Document);
        $Factory = $this->getFactory();
        $totalPrice = $calculatorService->getTotalPrice();
        $subPrice = $calculatorService->getSubTotal();
        $vatPrice = $calculatorService->getVatTotal();
        
        $formattedTotalPrice = Number::currency($totalPrice, $currency, config('app.locale'));
        $formattedSubPrice = Number::currency($subPrice, $currency, config('app.locale'));
        $normalizeCurrency = fn ($value) => $this->normalizePdfCurrency($value);

        $this->getDocumentLines($Document, 'invoiceLines');
        $image = $Factory->getImageFactoryPath();
        $resolver = app(PdfThemeResolver::class);
        $view = $resolver->resolveForDocument($Document, 'print/pdf-invoice', $Factory);
        $customCss = $Factory->pdf_custom_css;
        $dompdf = PDF::loadView($view, compact('typeDocumentName', 'Document', 'Factory', 'image', 'formattedTotalPrice', 'formattedSubPrice', 'vatPrice', 'customCss', 'normalizeCurrency'));

        // Récupération des informations client depuis le modèle associé
        $client = $Document->companie;
        $clientAddress = $client->Addresses();

        $zugferddatas = ZugferdDocumentBuilder::CreateNew(ZugferdProfiles::PROFILE_EN16931);
        $zugferddatas
        ->setDocumentInformation($Document->code, "380", \DateTime::createFromFormat("Ymd", "20180305"), $currency)
        ->addDocumentNote('Facture du ' . $Document->GetPrettyCreatedAttribute())
        
        // Ajout des informations du vendeur (Factory)
        ->setDocumentSeller($Factory->name, $Factory->id)
        ->addDocumentSellerTaxRegistration("VA", $Factory->vat_num)
        ->setDocumentSellerAddress(
            $Factory->address, 
            $Factory->zipcode, 
            $Factory->city, 
            $Factory->country
        )
        ->setDocumentSellerContact(
            $Factory->contact_name, 
            'N/A', 
            $Factory->phone_number, 
            'N/A',
            $Factory->mail
        )

        // Ajout des informations du client
        ->setDocumentBuyer($client->label, $client->code)
        //->addDocumentBuyerReference($Document->customer_reference)
        ->addDocumentBuyerTaxRegistration("VA", $client->intra_community_vat)
        ->setDocumentBuyerAddress(
            $clientAddress->adress ?? 'N/A', 
            'N/A',
            $clientAddress->zipcode ?? '00000', 
            $clientAddress->city ?? 'Unknown', 
            $clientAddress->country ?? 'XX'
        )

        // Ajout des informations de paiement
        ->addDocumentPaymentMean(ZugferdPaymentMeans::UNTDID_4461_58, null, null, null, null, null, $Factory->iban, null, null, null);

        // Ajout des taxes et lignes de produits
        foreach($vatPrice as $vat) {
            $zugferddatas->addDocumentTax("S", "VAT", $subPrice, $vat[1], $vat[0]); // $vat[1] est le montant, $vat[0] est le taux
        }
        $totalVAT = array_sum(array_column($vatPrice, 1));
        $zugferddatas->setDocumentSummation($totalPrice, $totalPrice, $subPrice, $totalVAT, 0.0, $subPrice, null, null, 0.0);
        //$zugferddatas->addDocumentPaymentTerm($Document->payment_condition['label']);

        // Ajout des lignes de facture à partir des `invoiceLines`
        foreach ($Document->Lines as $key => $line) {
            $zugferddatas->addNewPosition($key + 1)
                ->setDocumentPositionProductDetails($line->OrderLine['label'], $line->OrderLine['code'], $line->OrderLine['code'])
                ->setDocumentPositionGrossPrice($line->OrderLine->selling_price)
                ->setDocumentPositionNetPrice($line->OrderLine->selling_price)
                ->setDocumentPositionQuantity($line->qty, "H87")
                ->addDocumentPositionTax('S', 'VAT', $line->OrderLine->VAT['rate'])
                ->setDocumentPositionLineSummation($line->OrderLine->selling_price * $line->qty);
        }

        // Génération du document PDF
        $zugferdpdf = new ZugferdDocumentPdfBuilder($zugferddatas, $dompdf->stream());
        $pdfFilePath = public_path('pdf/invoices/' . $typeDocumentName . '-' . $Document->id . '.pdf');
        $zugferdpdf->generateDocument();
        $zugferdpdf->saveDocument($pdfFilePath);

        return response()->file($pdfFilePath);
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
    private function generatePdf($Document, $typeDocumentName, $calculatorService, $viewKey)
    {
        $factory = app('Factory');
        $currency = $factory->curency ?? 'EUR';
        $Factory = $this->getFactory();
        $totalPrice = $calculatorService ? $calculatorService->getTotalPrice() : 0;
        $subPrice = $calculatorService ? $calculatorService->getSubTotal() : 0;
        $vatPrice = $calculatorService ? $calculatorService->getVatTotal() : 0;

        $formattedTotalPrice = Number::currency($totalPrice, $currency, config('app.locale'));
        $formattedSubPrice = Number::currency($subPrice, $currency, config('app.locale'));
        $normalizeCurrency = fn ($value) => $this->normalizePdfCurrency($value);

        $this->getDocumentLines($Document, $this->getDocumentLinesKey($Document));
        $image = $Factory->getImageFactoryPath();
        $resolver = app(PdfThemeResolver::class);
        $resolvedView = $resolver->resolveForDocument($Document, $viewKey, $Factory);
        $customCss = $Factory->pdf_custom_css;
        $pdf = PDF::loadView($resolvedView, compact('typeDocumentName', 'Document', 'Factory', 'formattedTotalPrice', 'formattedSubPrice', 'vatPrice', 'image', 'customCss', 'normalizeCurrency'));

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

    /**
     * Normalize currency spacing for PDF fonts.
     *
     * @param mixed $value
     * @return string
     */
    private function normalizePdfCurrency($value): string
    {
        return str_replace(["\u{00A0}", "\u{202F}"], ' ', (string) $value);
    }
}
