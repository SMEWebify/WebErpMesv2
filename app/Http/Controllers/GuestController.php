<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use App\Services\OrderCalculatorService;
use App\Services\QuoteCalculatorService;
use App\Services\Documents\SalesPrintLayout;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\QuoteSignature;
use App\Services\Integrations\Signature\QuoteSignatureService;
use League\CommonMark\Extension\SmartPunct\Quote;

class GuestController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        return view('guest/guest');
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function ShowQuoteDocument($uuid)
    {
        $Quote = Quotes::where('uuid', $uuid)->first();
        if(empty($Quote)){
            return view('guest/guest');
        }
        
        $QuoteCalculatorService = new QuoteCalculatorService($Quote);
        $totalPrice = $QuoteCalculatorService->getTotalPrice();
        $subPrice = $QuoteCalculatorService->getSubTotal();
        $vatPrice = $QuoteCalculatorService->getVatTotal();
        $TotalServiceProductTime = $QuoteCalculatorService->getTotalProductTimeByService();
        $TotalServiceSettingTime = $QuoteCalculatorService->getTotalSettingTimeByService();
        $TotalServiceCost = $QuoteCalculatorService->getTotalCostByService();
        $TotalServicePrice = $QuoteCalculatorService->getTotalPriceByService();
        // Même mise en page que le PDF : sections, sous-totaux, lignes masquées omises.
        $printRows = app(SalesPrintLayout::class)->build($Quote->QuoteLines)['rows'];
        
        // Signature électronique : dernière enveloppe utile et possibilité de signer.
        $signatureService = app(QuoteSignatureService::class);
        $signatureEnabled = $signatureService->isAvailable();
        $signature = $Quote->signatures()->where('status', '!=', QuoteSignature::STATUS_VOIDED)->latest('id')->first();

        // Save visit information to database
        $this->logVisit(request(), $Quote->id);

        return view('guest/guest-quote-info', [
            'Quote' => $Quote,
            'totalPrices' => $totalPrice,
            'subPrice' => $subPrice, 
            'vatPrice' => $vatPrice,
            'TotalServiceProductTime'=> $TotalServiceProductTime,
            'TotalServiceSettingTime'=> $TotalServiceSettingTime,
            'TotalServiceCost'=> $TotalServiceCost,
            'TotalServicePrice'=> $TotalServicePrice,
            'printRows' => $printRows,
            'signature' => $signature,
            'canSign' => $signatureEnabled && $signatureService->canBeSigned($Quote),
        ]);
    }

    /**
     * Logs the visit information to the database.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $quoteId
     * @return void
     */
    private function logVisit(Request $request, $quoteId)
    {
        // Save information to the log table
        DB::table('guest_visits')->insert([
            'url_visited' => $request->url(),
            'visited_at' => now(),
            'quotes_id' => $quoteId,
            'visit_type' => 'quote' // Indicates the type of visit
        ]);
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function ShowOrderDocument($uuid)
    {
        $Order = Orders::where('uuid', $uuid)->first();
        if(empty($Order)){
            return view('guest/guest');
        }
        
        $OrderCalculatorService = new OrderCalculatorService($Order);
        $totalPrice = $OrderCalculatorService->getTotalPrice();
        $subPrice = $OrderCalculatorService->getSubTotal();
        $vatPrice = $OrderCalculatorService->getVatTotal();
        $TotalServiceProductTime = $OrderCalculatorService->getTotalProductTimeByService();
        $TotalServiceSettingTime = $OrderCalculatorService->getTotalSettingTimeByService();
        $TotalServiceCost = $OrderCalculatorService->getTotalCostByService();
        $TotalServicePrice = $OrderCalculatorService->getTotalPriceByService();
        
        return view('guest/guest-order-info', [
            'Order' => $Order,
            // Même mise en page que le PDF de commande : rien de masqué n'est montré.
            'lineRows' => app(SalesPrintLayout::class)->webRows($Order->OrderLines),
            'totalPrices' => $totalPrice,
            'subPrice' => $subPrice, 
            'vatPrice' => $vatPrice,
            'TotalServiceProductTime'=> $TotalServiceProductTime,
            'TotalServiceSettingTime'=> $TotalServiceSettingTime,
            'TotalServiceCost'=> $TotalServiceCost,
            'TotalServicePrice'=> $TotalServicePrice,
        ]);
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function ShowDeliveryDocument($uuid)
    {
        $Delivery = Deliverys::where('uuid', $uuid)->first();
        if(empty($Delivery)){
            return view('guest/guest');
        }
        
        return view('guest/guest-delivery-info', [
            'Delivery' => $Delivery,
        ]);
    }
}
