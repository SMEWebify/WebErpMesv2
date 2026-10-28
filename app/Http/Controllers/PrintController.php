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
use App\Models\Workflow\OrderConfirmations;
use App\Models\Purchases\PurchaseReceipt;
use App\Services\InvoiceCalculatorService;
use App\Services\PurchaseCalculatorService;
use App\Models\Purchases\PurchasesQuotation;
use App\Models\Quality\QualityNonConformity;
use App\Services\CreditNoteCalculatorService;
use App\Services\PdfThemeResolver;
use App\Services\Invoicing\FacturXBuilder;
use App\Services\Documents\SalesPrintLayout;
use App\Services\Documents\ProductionSheetBuilder;
use App\Services\Documents\DocumentPdfService;
use App\Services\Documents\PdfLayoutRegistry;
use Illuminate\Http\Request;

class PrintController extends Controller
{
    /**
     * @param Quotes $Document
     * @return \Illuminate\Contracts\View\View
     */
    public function getQuotePdf(Request $request, Quotes $Document)
    {
        return $this->salesPdf($request, $Document);
    }

    /**
     * @param Orders $Document
     * @return \Illuminate\Contracts\View\View
     */
    public function getOrderPdf(Request $request, Orders $Document)
    {
        return $this->salesPdf($request, $Document);
    }

    /**
     * ARC — rendu depuis les lignes figées du document, jamais depuis la commande.
     *
     * Un ARC en cours n'est pas imprimable : tant qu'il n'est pas envoyé il
     * n'engage rien, comme une facture au brouillon.
     *
     * @param OrderConfirmations $Document
     * @return \Illuminate\Contracts\View\View
     */
    public function getOrderConfirmPdf(Request $request, OrderConfirmations $Document)
    {
        abort_if((int) $Document->statu === OrderConfirmations::STATUS_IN_PROGRESS, 403, __('general_content.arc_draft_no_pdf_trans_key'));

        return $this->salesPdf($request, $Document);
    }

    /**
     * Devis, commande, AR : même rendu que les e-mails (DocumentPdfService), avec
     * le modèle d'impression (?layout=) et la langue (?lang=) choisis dans le menu
     * d'impression. Sans paramètre : le PDF d'avant. Une valeur hors liste → 404,
     * elle n'est jamais assemblée en nom de vue.
     */
    private function salesPdf(Request $request, $Document): \Symfony\Component\HttpFoundation\Response
    {
        $layout = $request->query('layout');
        $locale = $request->query('lang');
        $layout = is_string($layout) && $layout !== '' ? $layout : null;
        $locale = is_string($locale) && $locale !== '' ? $locale : null;

        $registry = app(PdfLayoutRegistry::class);
        abort_unless($registry->find('print/pdf-sales', $layout) && $registry->supportsLanguage($locale), 404);

        $service = app(DocumentPdfService::class);
        $bytes   = $service->render($Document, $layout, $locale);

        return response()->streamDownload(function () use ($bytes) {
            echo $bytes;
        }, $service->fileName($Document));
    }

    /**
     * Fiche suiveuse (ex-« instruction de fabrication ») : page de garde de la
     * commande, puis une fiche par ligne article. Même route qu'avant.
     *
     * @param Orders $Document
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function printOrderManufacturingInstruction(Orders $Document, ProductionSheetBuilder $builder)
    {
        $pdf = PDF::loadView('print/production-sheet', $builder->build($Document));

        return $pdf->stream(sprintf(
            __('production_sheet.file_name'),
            str_replace(['/', '\\'], '-', $Document->code)
        ));
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
        abort_if($Document->statu === 1, 403, __('general_content.invoice_draft_no_pdf_trans_key'));
        $typeDocumentName = __('general_content.invoice_trans_key');
        $calculatorService = new InvoiceCalculatorService($Document);
        return $this->generatePdf($Document, $typeDocumentName, $calculatorService, 'print/pdf-invoice');
    }

    /**
     * @param Invoices $Document
     * @return \Illuminate\Contracts\View\View
     */
    public function getProformaPdf(Invoices $Document)
    {
        abort_unless($Document->invoice_type === 3, 404);
        $calculatorService = new InvoiceCalculatorService($Document);
        return $this->generatePdf($Document, 'FACTURE PROFORMA', $calculatorService, 'print/pdf-invoice');
    }

    /**
     * Facture au format Factur-X (PDF/A-3 avec le XML CII en pièce jointe).
     *
     * Le document est construit en mémoire par FacturXBuilder et renvoyé tel
     * quel : c'est exactement le même octet pour octet que celui déposé sur la
     * PDP. Il n'est plus écrit dans public/ (document légal nominatif, il y
     * était servi sans authentification).
     *
     * FacturXBuilder refuse déjà brouillons et proformas ; on garde ici pour
     * répondre 403/404 plutôt que de laisser remonter une 500.
     */
    public function getInvoiceFactureX(Invoices $Document, FacturXBuilder $builder)
    {
        abort_if($Document->statu === 1, 403, __('general_content.invoice_draft_no_facturx_trans_key'));
        abort_if($Document->invoice_type === 3, 404, __('general_content.invoice_proforma_no_facturx_trans_key'));

        $filename = __('general_content.invoice_trans_key') . '-' . $Document->code . '.pdf';

        try {
            $pdf = $builder->buildPdf($Document);
        } catch (\RuntimeException $e) {
            // Données manquantes (SIREN, TVA, adresse…) : le message est rédigé
            // pour l'utilisateur, on l'affiche au lieu d'une 500 — même rendu
            // titre + puces que l'aperçu de l'envoi par mail.
            $lines = preg_split('/\r?\n/', trim($e->getMessage()));

            return response()->view('print.facturx-error', [
                'invoice' => $Document,
                'title'   => array_shift($lines),
                'items'   => array_values(array_filter(array_map(fn ($l) => ltrim(trim($l), "—- \t"), $lines))),
            ], 422);
        }

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
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
    private function generatePdf($Document, $typeDocumentName, $calculatorService, $viewKey): \Symfony\Component\HttpFoundation\Response
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
        $printRows = $viewKey === 'print/pdf-sales' ? app(SalesPrintLayout::class)->apply($Document) : null;
        $image = $Factory->getImageFactoryPath();
        $resolver = app(PdfThemeResolver::class);
        $resolvedView = $resolver->resolveForDocument($Document, $viewKey, $Factory);
        $customCss = $Factory->pdf_custom_css;
        $pdf = PDF::loadView($resolvedView, compact('typeDocumentName', 'Document', 'Factory', 'formattedTotalPrice', 'formattedSubPrice', 'vatPrice', 'image', 'customCss', 'normalizeCurrency', 'printRows'));

        // Render first so all pages exist, then add page numbers on every page
        $pdf->render();
        $canvas = $pdf->getDomPDF()->getCanvas();
        $font   = $pdf->getDomPDF()->getFontMetrics()->getFont('helvetica', 'normal');
        $canvas->page_text(470, 778, 'Page {PAGE_NUM} / {PAGE_COUNT}', $font, 7, [0.3, 0.3, 0.3]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, str_replace(['/', '\\'], '-', $Document->code) . '.pdf');
    }

    /**
     * Get the factory instance.
     *
     * @return \App\Models\Admin\Factory
     */
    private function getFactory()
    {
        return app('Factory');
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
            case OrderConfirmations::class:
                return 'OrderConfirmationLines';
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
