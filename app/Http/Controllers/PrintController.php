<?php

namespace App\Http\Controllers;


use PDF;
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
use Webklex\PDFMerger\Facades\PDFMergerFacade as PDFMerger;

class PrintController extends Controller
{

    /**
     * @param $Document
     * @return \Illuminate\Contracts\View\View
     */
    public function getQuotePdf(Quotes $Document)
    {
        $typeDocumentName = __('general_content.quote_trans_key'); 
        $QuoteCalculatorService = new QuoteCalculatorService($Document);
        $Factory = Factory::first();
        $totalPrices = $QuoteCalculatorService->getTotalPrice();
        $subPrice = $QuoteCalculatorService->getSubTotal();
        $vatPrice = $QuoteCalculatorService->getVatTotal();
        $Document->Lines = $Document->QuoteLines;
        unset($Document->QuoteLines);
        $image= $Factory->getImageFactoryPath();
        //Start PDF generate
        $oMerger = PDFMerger::init();
        //Creat quote view
        $pdf = PDF::loadView('print/pdf-sales', compact('typeDocumentName','Document', 'Factory','totalPrices','subPrice','vatPrice','image'));
        // Save temp PDF 
        $tempPath = tempnam(sys_get_temp_dir(), 'dompdf_temp');
        $pdf->save($tempPath);
        // add le PDF to merg
        $oMerger->addPDF($tempPath, 'all');
        if ( $Factory->cgv_file && $Factory->add_cgv_to_pdf != 2){
            // add cgv if is required
            $oMerger->addPDF('cgv/factory/' . $Factory->cgv_file, 'all');
        }
        // merge
        $oMerger->merge();
        //display
        return $oMerger->stream();
    }
    
    /**
     * @param $Document
     * @return \Illuminate\Contracts\View\View
     */
    public function getOrderPdf(Orders $Document)
    {
        $typeDocumentName = __('general_content.order_trans_key');
        $OrderCalculatorService = new OrderCalculatorService($Document);
        $Factory = Factory::first();
        $totalPrices = $OrderCalculatorService->getTotalPrice();
        $subPrice = $OrderCalculatorService->getSubTotal();
        $vatPrice = $OrderCalculatorService->getVatTotal();
        $Document->Lines = $Document->OrderLines;
        unset($Document->OrderLines);
        $image= $Factory->getImageFactoryPath();
        //Start PDF generate
        $oMerger = PDFMerger::init();
        //Creat quote view
        $pdf = PDF::loadView('print/pdf-sales', compact('typeDocumentName','Document', 'Factory','totalPrices','subPrice','vatPrice','image'));
        // Save temp PDF 
        $tempPath = tempnam(sys_get_temp_dir(), 'dompdf_temp');
        $pdf->save($tempPath);
        // add le PDF to merg
        $oMerger->addPDF($tempPath, 'all');
        if ( $Factory->cgv_file && $Factory->add_cgv_to_pdf != 2){
            // add cgv if is required
            $oMerger->addPDF('cgv/factory/' . $Factory->cgv_file, 'all');
        }
        // merge
        $oMerger->merge();
        //display
        return $oMerger->stream();

        
    }
    
    /**
     * @param $Document
     * @return \Illuminate\Contracts\View\View
     */
    public function getOrderConfirmPdf(Orders $Document)
    {
        $typeDocumentName = __('general_content.order_confirm_trans_key');
        $OrderCalculatorService = new OrderCalculatorService($Document);
        $Factory = Factory::first();
        $totalPrices = $OrderCalculatorService->getTotalPrice();
        $subPrice = $OrderCalculatorService->getSubTotal();
        $vatPrice = $OrderCalculatorService->getVatTotal();
        $Document->Lines = $Document->OrderLines;
        unset($Document->OrderLines);
        $image= $Factory->getImageFactoryPath();
        //Start PDF generate
        $oMerger = PDFMerger::init();
        //Creat quote view
        $pdf = PDF::loadView('print/pdf-sales', compact('typeDocumentName','Document', 'Factory','totalPrices','subPrice','vatPrice','image'));
        // Save temp PDF 
        $tempPath = tempnam(sys_get_temp_dir(), 'dompdf_temp');
        $pdf->save($tempPath);
        // add le PDF to merg
        $oMerger->addPDF($tempPath, 'all');
        if ( $Factory->cgv_file && $Factory->add_cgv_to_pdf != 2){
            // add cgv if is required
            $oMerger->addPDF('cgv/factory/' . $Factory->cgv_file, 'all');
        }
        // merge
        $oMerger->merge();
        //display
        return $oMerger->stream();
    }
    
    /**
     * @param $Document
     * @return \Illuminate\Contracts\View\View
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
     * @return \Illuminate\Contracts\View\View
     */
    public function getDeliveryPdf(Deliverys $Document)
    {
        $typeDocumentName = __('general_content.delivery_notes_trans_key');
        $Factory = Factory::first();
        $Document->Lines = $Document->DeliveryLines;
        unset($Document->DeliveryLines);
        $image= $Factory->getImageFactoryPath();;
        $pdf = PDF::loadView('print/pdf-delivery', compact('typeDocumentName','Document', 'Factory','image'));
        return $pdf->stream();
    }
    
    
    /**
     * @param $Document
     * @return \Illuminate\Contracts\View\View
     */
    public function getInvoicePdf(Invoices $Document)
    {
        $typeDocumentName = __('general_content.invoice_trans_key');
        $InvoiceCalculatorService = new InvoiceCalculatorService($Document);
        $Factory = Factory::first();
        $totalPrices = $InvoiceCalculatorService->getTotalPrice();
        $subPrice = $InvoiceCalculatorService->getSubTotal();
        $vatPrice = $InvoiceCalculatorService->getVatTotal();
        $Document->Lines = $Document->invoiceLines;
        unset($Document->invoiceLines);
        $image= $Factory->getImageFactoryPath();;
        $pdf = PDF::loadView('print/pdf-invoice', compact('typeDocumentName','Document', 'Factory','image','totalPrices','subPrice','vatPrice','image'));
        return $pdf->stream();
    }

    
    public function getInvoiceFactureX(Invoices $Document)
    {

        $typeDocumentName = __('general_content.invoice_trans_key');
        $InvoiceCalculatorService = new InvoiceCalculatorService($Document);
        $Factory = Factory::first();
        $totalPrices = $InvoiceCalculatorService->getTotalPrice();
        $subPrice = $InvoiceCalculatorService->getSubTotal();
        $vatPrice = $InvoiceCalculatorService->getVatTotal();
        $Document->Lines = $Document->invoiceLines;
        unset($Document->invoiceLines);
        $image= $Factory->getImageFactoryPath();;
        $dompdf = PDF::loadView('print/pdf-invoice', compact('typeDocumentName','Document', 'Factory','image','totalPrices','subPrice','vatPrice','image'));

        $zugferddatas = ZugferdDocumentBuilder::CreateNew(ZugferdProfiles::PROFILE_EN16931);
        $zugferddatas
            //<ram:ID>471102</ram:ID>
            //<ram:TypeCode>380</ram:TypeCode>
            //<ram:IssueDateTime>
            //<udt:DateTimeString format="102">20180305</udt:DateTimeString>
            //</ram:IssueDateTime>
            ->setDocumentInformation("471102", "380", \DateTime::createFromFormat("Ymd", "20180305"), "EUR")
            //<ram:IncludedNote>
            //<ram:Content>Rechnung gemäß Bestellung vom 01.03.2018.</ram:Content>
            //</ram:IncludedNote>
            ->addDocumentNote('Rechnung gemäß Bestellung vom 01.03.2018.')
            //<ram:IncludedNote>
            //<ram:Content>Lieferant GmbH&#13;
            //Lieferantenstraße 20&#13;
            //80333 München&#13;
            //Deutschland&#13;
            //Geschäftsführer: Hans Muster&#13;
            //Handelsregisternummer: H A 123&#13;
            //&#13;
            //</ram:Content>
            ->addDocumentNote('Lieferant GmbH' . PHP_EOL . 'Lieferantenstraße 20' . PHP_EOL . '80333 München' . PHP_EOL . 'Deutschland' . PHP_EOL . 'Geschäftsführer: Hans Muster' . PHP_EOL . 'Handelsregisternummer: H A 123' . PHP_EOL . PHP_EOL, null, 'REG')
            //<ram:IssueDateTime>
            //<udt:DateTimeString format="102">20180305</udt:DateTimeString>
            //</ram:IssueDateTime>
            ->setDocumentSupplyChainEvent(\DateTime::createFromFormat('Ymd', '20180305'))
            //<ram:IBANID>DE12500105170648489890</ram:IBANID>
            ->addDocumentPaymentMean(ZugferdPaymentMeans::UNTDID_4461_58, null, null, null, null, null, "DE12500105170648489890", null, null, null)
            //<ram:ID>549910</ram:ID>
            //<ram:Name>Lieferant GmbH</ram:Name>
            ->setDocumentSeller("Lieferant GmbH", "549910")
            //<ram:GlobalID schemeID="0088">4000001123452</ram:GlobalID>
            ->addDocumentSellerGlobalId("4000001123452", "0088")
            //<ram:SpecifiedTaxRegistration>
            //<ram:ID schemeID="FC">201/113/40209</ram:ID>
            //</ram:SpecifiedTaxRegistration>
            ->addDocumentSellerTaxRegistration("FC", "201/113/40209")
            //Identifiant à la TVA du vendeur
            //<ram:SpecifiedTaxRegistration>
            //<ram:ID schemeID="VA">DE123456789</ram:ID>
            //</ram:SpecifiedTaxRegistration>
            ->addDocumentSellerTaxRegistration("VA", "DE123456789")
            //<ram:PostalTradeAddress>
            //<ram:PostcodeCode>80333</ram:PostcodeCode>
            //<ram:LineOne>Lieferantenstraße 20</ram:LineOne>
            //<ram:CityName>München</ram:CityName>
            //<ram:CountryID>DE</ram:CountryID>
            //</ram:PostalTradeAddress>
            ->setDocumentSellerAddress("Lieferantenstraße 20", "", "", "80333", "München", "DE")
            //<ram:DefinedTradeContact>
            //<ram:PersonName>Heinz Mükker</ram:PersonName>
            //<ram:DepartmentName>Buchhaltung</ram:DepartmentName>
            //<ram:TelephoneUniversalCommunication>
            //<ram:CompleteNumber>+49-111-2222222</ram:CompleteNumber>
            //</ram:TelephoneUniversalCommunication>
            //<ram:EmailURIUniversalCommunication>
            //<ram:URIID>info@lieferant.de</ram:URIID>
            //</ram:EmailURIUniversalCommunication>
            //</ram:DefinedTradeContact>
            ->setDocumentSellerContact("Heinz Mükker", "Buchhaltung", "+49-111-2222222", "+49-111-3333333","info@lieferant.de")
            //<ram:ID>GE2020211</ram:ID>
            //<ram:Name>Kunden AG Mitte</ram:Name>
            ->setDocumentBuyer("Kunden AG Mitte", "GE2020211")
            //<ram:BuyerReference>34676-342323</ram:BuyerReference>
            ->setDocumentBuyerReference("34676-342323")
            //<ram:PostalTradeAddress>
            //<ram:PostcodeCode>69876</ram:PostcodeCode>
            //<ram:LineOne>Kundenstraße 15</ram:LineOne>
            //<ram:CityName>Frankfurt</ram:CityName>
            //<ram:CountryID>DE</ram:CountryID>
            //</ram:PostalTradeAddress>
            ->setDocumentBuyerAddress("Kundenstraße 15", "", "", "69876", "Frankfurt", "DE")
            //<ram:TypeCode>VAT</ram:TypeCode>
            //<ram:CategoryCode>S</ram:CategoryCode>
            //<ram:RateApplicablePercent>7.00</ram:RateApplicablePercent>
            //<ram:SpecifiedTradeSettlementLineMonetarySummation>
            //<ram:LineTotalAmount>275.00</ram:LineTotalAmount>
            ->addDocumentTax("S", "VAT", 275.0, 19.25, 7.0)
            //<ram:CalculatedAmount>37.02</ram:CalculatedAmount>
            //<ram:TypeCode>VAT</ram:TypeCode>
            //<ram:BasisAmount>198.00</ram:BasisAmount>
            //<ram:CategoryCode>S</ram:CategoryCode>
            //<ram:RateApplicablePercent>19.00</ram:RateApplicablePercent>
            ->addDocumentTax("S", "VAT", 198.0, 37.62, 19.0)
            //<ram:SpecifiedTradeSettlementHeaderMonetarySummation>
            //<ram:LineTotalAmount>473.00</ram:LineTotalAmount>
            //<ram:ChargeTotalAmount>0.00</ram:ChargeTotalAmount>
            //<ram:AllowanceTotalAmount>0.00</ram:AllowanceTotalAmount>
            //<ram:TaxBasisTotalAmount>473.00</ram:TaxBasisTotalAmount>
            //<ram:TaxTotalAmount currencyID="EUR">56.87</ram:TaxTotalAmount>
            //<ram:GrandTotalAmount>529.87</ram:GrandTotalAmount>
            //<ram:TotalPrepaidAmount>0.00</ram:TotalPrepaidAmount>
            //<ram:DuePayableAmount>529.87</ram:DuePayableAmount>
            //</ram:SpecifiedTradeSettlementHeaderMonetarySummation>
            ->setDocumentSummation(529.87, 529.87, 473.00, 0.0, 0.0, 473.00, 56.87, null, 0.0)
            //<ram:SpecifiedTradePaymentTerms>
            //<ram:Description>Zahlbar innerhalb 30 Tagen netto bis 04.04.2018, 3% Skonto innerhalb 10 Tagen bis 15.03.2018</ram:Description>
            //</ram:SpecifiedTradePaymentTerms>
            ->addDocumentPaymentTerm("Zahlbar innerhalb 30 Tagen netto bis 04.04.2018, 3% Skonto innerhalb 10 Tagen bis 15.03.2018")


            //<ram:AssociatedDocumentLineDocument>
            //<ram:LineID>1</ram:LineID>
            //</ram:AssociatedDocumentLineDocument>
            ->addNewPosition("1")
            //<ram:SpecifiedTradeProduct>
            //<ram:GlobalID schemeID="0160">4012345001235</ram:GlobalID>
            //<ram:SellerAssignedID>TB100A4</ram:SellerAssignedID>
            //<ram:Name>Trennblätter A4</ram:Name>
            //</ram:SpecifiedTradeProduct>
            ->setDocumentPositionProductDetails("Trennblätter A4", "", "TB100A4", null, "0160", "4012345001235")
            //<ram:GrossPriceProductTradePrice>
            //<ram:ChargeAmount>9.90</ram:ChargeAmount>
            //</ram:GrossPriceProductTradePrice>
            ->setDocumentPositionGrossPrice(9.9000)
            //<ram:NetPriceProductTradePrice>
            //<ram:ChargeAmount>9.90</ram:ChargeAmount>
            //</ram:NetPriceProductTradePrice>
            ->setDocumentPositionNetPrice(9.9000)
            //<ram:BilledQuantity unitCode="H87">50.00</ram:BilledQuantity>
            ->setDocumentPositionQuantity(20, "H87")
            //<ram:ApplicableTradeTax>
            //<ram:TypeCode>VAT</ram:TypeCode>
            //<ram:CategoryCode>S</ram:CategoryCode>
            //<ram:RateApplicablePercent>19.00</ram:RateApplicablePercent>
            //</ram:ApplicableTradeTax>
            ->addDocumentPositionTax('S', 'VAT', 19)
            //<ram:LineTotalAmount>198.00</ram:LineTotalAmount>
            ->setDocumentPositionLineSummation(198.0);
        
            $zugferdpdf = new ZugferdDocumentPdfBuilder($zugferddatas, $dompdf->stream() );
            $zugferdpdf->generateDocument();
            $zugferdpdf->saveDocument(public_path('pdf\invoices') . '\\'. $typeDocumentName .'-'. $Document->id .'.pdf');
            return response()->file(public_path('pdf\invoices') . '\\'. $typeDocumentName .'-'. $Document->id .'.pdf');
    }


    
    public function getCreditNotePdf(CreditNotes $Document)
    {
        $typeDocumentName = __('general_content.credit_note_trans_key');
        $CreditNoteCalculatorService = new CreditNoteCalculatorService($Document);
        $Factory = Factory::first();
        $totalPrices = $CreditNoteCalculatorService->getTotalPrice();
        $subPrice = $CreditNoteCalculatorService->getSubTotal();
        $vatPrice = $CreditNoteCalculatorService->getVatTotal();
        $Document->Lines = $Document->creditNotelines;
        unset($Document->creditNotelines);
        $image= $Factory->getImageFactoryPath();;
        $pdf = PDF::loadView('print/pdf-credit-note', compact('typeDocumentName','Document', 'Factory','image','totalPrices','subPrice','vatPrice','image'));
        return $pdf->stream();
    }
    
    
    /**
     * @param $Document
     * @return \Illuminate\Contracts\View\View
     */
    public function getPurchaseQuotationPdf(PurchasesQuotation $Document)
    {
        $typeDocumentName = __('general_content.purchase_request_trans_key');
        $Factory = Factory::first();
        $Document->Lines = $Document->PurchaseQuotationLines;
        unset($Document->PurchaseQuotationLines);
        $image= $Factory->getImageFactoryPath();;
        $pdf = PDF::loadView('print/pdf-purchases-quotation', compact('typeDocumentName','Document', 'Factory','image'));
        return $pdf->stream();
    }
    

    /**
     * @param $Document
     * @return \Illuminate\Contracts\View\View
     */
    public function getPurchasePdf(Purchases $Document)
    {
        $typeDocumentName = __('general_content.purchase_order_trans_key');
        $PurchaseCalculatorService = new PurchaseCalculatorService($Document);
        $Factory = Factory::first();
        $totalPrices = $PurchaseCalculatorService->getTotalPrice();
        $Document->Lines = $Document->PurchaseLines;
        unset($Document->PurchaseLines);
        $image= $Factory->getImageFactoryPath();;
        $pdf = PDF::loadView('print/pdf-purchases', compact('typeDocumentName','Document', 'Factory','totalPrices','image'));
        return $pdf->stream();
    }

    /**
     * @param $Document
     * @return \Illuminate\Contracts\View\View
     */
    public function getReceiptPdf(PurchaseReceipt $Document)
    {
        $typeDocumentName = __('general_content.po_receipt_trans_key');
        $Factory = Factory::first();
        $Document->Lines = $Document->PurchaseReceiptLines;
        unset($Document->PurchaseReceiptLines);
        $image= $Factory->getImageFactoryPath();;
        $pdf = PDF::loadView('print/pdf-purchases-reciept', compact('typeDocumentName','Document', 'Factory','image'));
        return $pdf->stream();
    }

    /**
     * @param $Document
     * @return \Illuminate\Contracts\View\View
     */
    public function getNCPdf(QualityNonConformity $Document)
    {
        $typeDocumentName = __('general_content.non_conformitie_trans_key');
        $Factory = Factory::first();
        $image= $Factory->getImageFactoryPath();;
        $pdf = PDF::loadView('print/pdf-nc', compact('typeDocumentName','Document', 'Factory','image'));
        return $pdf->stream();
    }

}
