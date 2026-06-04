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
        // Adresse de facturation : celle liée à la facture en priorité, sinon
        // l'adresse par défaut du client, sinon la première disponible.
        $clientAddress = $Document->adresse
            ?? $client->Addresses()->where('default', 1)->first()
            ?? $client->Addresses()->first();

        // Type de document (UNTDID 1001) selon le type de facture interne.
        $documentTypeCode = [
            1 => '380', // facture
            2 => '381', // avoir
            3 => '380', // proforma (traité comme facture)
            4 => '386', // facture d'acompte
        ][$Document->invoice_type] ?? '380';

        $issueDate = $Document->created_at instanceof \DateTimeInterface
            ? $Document->created_at
            : \Carbon\Carbon::parse($Document->created_at);

        $vatBreakdown = $calculatorService->getVatBreakdown();
        $lines        = $calculatorService->getNormalizedLines();
        $totalVAT     = round(array_sum(array_column($vatBreakdown, 'vat')), 2);

        $zugferddatas = ZugferdDocumentBuilder::CreateNew(ZugferdProfiles::PROFILE_EN16931);
        $zugferddatas
        ->setDocumentInformation($Document->code, $documentTypeCode, $issueDate, $currency)
        ->addDocumentNote('Facture ' . $Document->code . ' du ' . $issueDate->format('d/m/Y'))

        // Référence acheteur (BT-10) : référence commande/marché côté client.
        ->setDocumentBuyerReference($Document->customer_reference ?: $Document->code)

        // Ajout des informations du vendeur (Factory)
        ->setDocumentSeller($Factory->name)
        ->setDocumentSellerLegalOrganisation($Factory->siren, '0002', $Factory->name)
        ->addDocumentSellerTaxRegistration('VA', $Factory->vat_num)
        ->setDocumentSellerAddress(
            $Factory->address,
            null,
            null,
            $Factory->zipcode,
            $Factory->city,
            $this->factureXCountryCode($Factory->country)
        )
        ->setDocumentSellerContact(
            $Factory->name,
            null,
            $Factory->phone_number,
            null,
            $Factory->mail
        )

        // Ajout des informations du client
        ->setDocumentBuyer($client->label, $client->code)
        ->setDocumentBuyerAddress(
            $clientAddress->adress ?? 'N/A',
            null,
            null,
            $clientAddress->zipcode ?? '00000',
            $clientAddress->city ?? 'N/A',
            $this->factureXCountryCode($clientAddress->country ?? null)
        );

        if ($client->siren) {
            $zugferddatas->setDocumentBuyerLegalOrganisation($client->siren, '0002', $client->label);
        }
        if ($client->intra_community_vat) {
            $zugferddatas->addDocumentBuyerTaxRegistration('VA', $client->intra_community_vat);
        }

        // Moyen et conditions de paiement
        if ($Factory->iban) {
            $zugferddatas->addDocumentPaymentMeanToCreditTransfer(
                $Factory->iban,
                $Factory->name,
                null,
                $Factory->bic ?: null
            );
        }
        if ($Document->due_date) {
            $dueDate = \Carbon\Carbon::parse($Document->due_date);
            $zugferddatas->addDocumentPaymentTerm('Échéance le ' . $dueDate->format('d/m/Y'), $dueDate);
        }

        // Ventilation de la TVA (BG-23) : une ligne par taux, base + montant.
        foreach ($vatBreakdown as $vat) {
            $category = $vat['rate'] > 0 ? 'S' : 'Z'; // S = taux standard, Z = taux zéro
            $zugferddatas->addDocumentTax($category, 'VAT', round($vat['base'], 2), round($vat['vat'], 2), $vat['rate']);
        }

        // Totaux : grand total, dû, total lignes, charges, remises, base TVA, TVA, arrondi, payé.
        $zugferddatas->setDocumentSummation(
            round($totalPrice, 2),
            round($Document->remaining_amount ?? $totalPrice, 2),
            round($subPrice, 2),
            0.0,
            0.0,
            round($subPrice, 2),
            $totalVAT,
            0.0,
            round($Document->paid_amount ?? 0, 2)
        );

        // Lignes de facture (snapshot des prix, remise et TVA figés)
        foreach ($lines as $key => $line) {
            $zugferddatas->addNewPosition($key + 1)
                ->setDocumentPositionProductDetails($line['label'] ?: $line['code'], null, $line['code'])
                ->setDocumentPositionGrossPrice(round($line['unit_price'], 2));

            if ($line['discount'] > 0) {
                $zugferddatas->addDocumentPositionGrossPriceAllowanceCharge(
                    round($line['unit_price'] * $line['discount'] / 100, 2),
                    false, // remise (allowance), pas une charge
                    $line['discount'],
                    round($line['unit_price'], 2),
                    'Remise'
                );
            }

            $zugferddatas->setDocumentPositionNetPrice(round($line['net_unit_price'], 2))
                ->setDocumentPositionQuantity($line['qty'], $this->factureXUnitCode($line['unit_code']))
                ->addDocumentPositionTax($line['vat_rate'] > 0 ? 'S' : 'Z', 'VAT', $line['vat_rate'])
                ->setDocumentPositionLineSummation(round($line['line_total'], 2));
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
        $image = $Factory->getImageFactoryPath();
        $resolver = app(PdfThemeResolver::class);
        $resolvedView = $resolver->resolveForDocument($Document, $viewKey, $Factory);
        $customCss = $Factory->pdf_custom_css;
        $pdf = PDF::loadView($resolvedView, compact('typeDocumentName', 'Document', 'Factory', 'formattedTotalPrice', 'formattedSubPrice', 'vatPrice', 'image', 'customCss', 'normalizeCurrency'));

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

    /**
     * Normalise un pays en code ISO 3166-1 alpha-2 (requis EN 16931, BT-40/BT-55).
     *
     * Accepte déjà un code à 2 lettres, sinon convertit quelques libellés
     * courants. Faute de correspondance, retombe sur 'FR' (ERP français).
     */
    private function factureXCountryCode(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return 'FR';
        }

        if (preg_match('/^[A-Za-z]{2}$/', $value)) {
            return strtoupper($value);
        }

        $map = [
            'france' => 'FR', 'belgique' => 'BE', 'belgium' => 'BE',
            'allemagne' => 'DE', 'germany' => 'DE', 'espagne' => 'ES', 'spain' => 'ES',
            'italie' => 'IT', 'italy' => 'IT', 'suisse' => 'CH', 'switzerland' => 'CH',
            'luxembourg' => 'LU', 'pays-bas' => 'NL', 'netherlands' => 'NL',
            'portugal' => 'PT', 'royaume-uni' => 'GB', 'united kingdom' => 'GB',
        ];

        return $map[mb_strtolower($value)] ?? 'FR';
    }

    /**
     * Convertit l'unité interne en code unité UN/ECE Rec. 20 (EN 16931, BT-130).
     * Par défaut 'C62' (unité/pièce), code générique recommandé par la norme.
     */
    private function factureXUnitCode(?string $code): string
    {
        $map = [
            'pcs' => 'C62', 'pc' => 'C62', 'u' => 'C62', 'unite' => 'C62', 'piece' => 'C62',
            'kg' => 'KGM', 'g' => 'GRM', 't' => 'TNE',
            'm' => 'MTR', 'ml' => 'MTR', 'm2' => 'MTK', 'm²' => 'MTK', 'm3' => 'MTQ', 'm³' => 'MTQ',
            'l' => 'LTR', 'h' => 'HUR', 'heure' => 'HUR',
        ];

        return $map[mb_strtolower(trim((string) $code))] ?? 'C62';
    }
}
