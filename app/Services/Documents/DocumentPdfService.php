<?php

namespace App\Services\Documents;

use App\Models\Purchases\Purchases;
use App\Models\Purchases\PurchaseReceipt;
use App\Models\Purchases\PurchasesQuotation;
use App\Models\Quality\QualityNonConformity;
use App\Models\Workflow\CreditNotes;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\OrderConfirmations;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use App\Services\CreditNoteCalculatorService;
use App\Services\Invoicing\FacturXBuilder;
use App\Services\InvoiceCalculatorService;
use App\Services\OrderCalculatorService;
use App\Services\OrderConfirmationCalculatorService;
use App\Services\PdfThemeResolver;
use App\Services\PurchaseCalculatorService;
use App\Services\QuoteCalculatorService;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Number;

/**
 * Rend un document (devis, commande, facture…) en PDF renvoyé sous forme de
 * bytes bruts.
 *
 * Sert à deux usages : le téléchargement via PrintController (qui l'enveloppe
 * dans une streamDownload), et l'auto-attachement des PDF dans les emails
 * partant de EmailController. Sans ce service commun, l'attachement email
 * dupliquerait tout le pipeline dompdf.
 *
 * Pour les factures non-brouillon non-proforma, on renvoie le Factur-X (PDF/A-3
 * + XML CII embarqué) — c'est ce que le client attend juridiquement, et c'est
 * ce qui est déposé sur la PDP. Sinon, rendu dompdf standard.
 */
class DocumentPdfService
{
    public function __construct(
        private readonly PdfThemeResolver $themeResolver,
        private readonly FacturXBuilder $facturXBuilder,
    ) {}

    public function fileName($document): string
    {
        return str_replace(['/', '\\'], '-', $document->code) . '.pdf';
    }

    /**
     * Rend le PDF du document et renvoie les octets bruts.
     */
    public function render($document): string
    {
        // Facture émise (non brouillon, non proforma) → Factur-X, source de vérité légale.
        if ($document instanceof Invoices
            && (int) $document->statu !== 1
            && (int) $document->invoice_type !== 3
        ) {
            return $this->facturXBuilder->buildPdf($document);
        }

        [$viewKey, $calculator, $typeDocumentName] = $this->resolveViewAndCalculator($document);

        $factory  = app('Factory');
        $currency = $factory->curency ?? 'EUR';

        $totalPrice = $calculator ? $calculator->getTotalPrice() : 0;
        $subPrice   = $calculator ? $calculator->getSubTotal()   : 0;
        $vatPrice   = $calculator ? $calculator->getVatTotal()   : 0;

        $formattedTotalPrice = Number::currency($totalPrice, $currency, config('app.locale'));
        $formattedSubPrice   = Number::currency($subPrice,   $currency, config('app.locale'));
        $normalizeCurrency   = fn ($value) => str_replace(["\u{00A0}", "\u{202F}"], ' ', (string) $value);

        $this->exposeDocumentLines($document);
        $image        = $factory->getImageFactoryPath();
        $resolvedView = $this->themeResolver->resolveForDocument($document, $viewKey, $factory);
        $customCss    = $factory->pdf_custom_css;

        $Factory = $factory; // Alias attendu par les vues legacy.
        $Document = $document;

        $pdf = PDF::loadView($resolvedView, compact(
            'typeDocumentName',
            'Document',
            'Factory',
            'formattedTotalPrice',
            'formattedSubPrice',
            'vatPrice',
            'image',
            'customCss',
            'normalizeCurrency',
        ));

        $pdf->render();
        $canvas = $pdf->getDomPDF()->getCanvas();
        $font   = $pdf->getDomPDF()->getFontMetrics()->getFont('helvetica', 'normal');
        $canvas->page_text(470, 778, 'Page {PAGE_NUM} / {PAGE_COUNT}', $font, 7, [0.3, 0.3, 0.3]);

        return (string) $pdf->output();
    }

    /**
     * @return array{0:string,1:mixed,2:string} [viewKey, calculator, typeDocumentName]
     */
    private function resolveViewAndCalculator($document): array
    {
        return match (true) {
            $document instanceof Quotes              => ['print/pdf-sales',              new QuoteCalculatorService($document),             __('general_content.quote_trans_key')],
            $document instanceof Orders              => ['print/pdf-sales',              new OrderCalculatorService($document),             __('general_content.order_trans_key')],
            $document instanceof OrderConfirmations  => ['print/pdf-sales',              new OrderConfirmationCalculatorService($document), __('general_content.order_confirm_trans_key') . ' ' . $document->revision],
            $document instanceof Deliverys           => ['print/pdf-delivery',           null,                                              __('general_content.delivery_notes_trans_key')],
            $document instanceof Invoices            => ['print/pdf-invoice',            new InvoiceCalculatorService($document),           $document->invoice_type === 3 ? 'FACTURE PROFORMA' : __('general_content.invoice_trans_key')],
            $document instanceof CreditNotes         => ['print/pdf-credit-note',        new CreditNoteCalculatorService($document),        __('general_content.credit_note_trans_key')],
            $document instanceof PurchasesQuotation  => ['print/pdf-purchases-quotation', null,                                             __('general_content.purchase_request_trans_key')],
            $document instanceof Purchases           => ['print/pdf-purchases',          new PurchaseCalculatorService($document),          __('general_content.purchase_order_trans_key')],
            $document instanceof PurchaseReceipt     => ['print/pdf-purchases-receipt', null,                                               __('general_content.po_receipt_trans_key')],
            $document instanceof QualityNonConformity => ['print/pdf-nc',                null,                                               __('general_content.non_conformitie_trans_key')],
            default => throw new \InvalidArgumentException('Document non pris en charge : ' . get_class($document)),
        };
    }

    /**
     * Expose les lignes sous `Lines` — clé attendue par les vues PDF legacy.
     */
    private function exposeDocumentLines($document): void
    {
        $key = match (true) {
            $document instanceof Quotes              => 'QuoteLines',
            $document instanceof Orders              => 'OrderLines',
            $document instanceof OrderConfirmations  => 'OrderConfirmationLines',
            $document instanceof Deliverys           => 'DeliveryLines',
            $document instanceof Invoices            => 'invoiceLines',
            $document instanceof CreditNotes         => 'creditNotelines',
            $document instanceof PurchasesQuotation  => 'PurchaseQuotationLines',
            $document instanceof Purchases           => 'PurchaseLines',
            $document instanceof PurchaseReceipt     => 'PurchaseReceiptLines',
            $document instanceof QualityNonConformity => 'QualityNonConformityLines',
            default => throw new \InvalidArgumentException('Unknown document type'),
        };

        $document->Lines = $document->$key;
        unset($document->$key);
    }
}
