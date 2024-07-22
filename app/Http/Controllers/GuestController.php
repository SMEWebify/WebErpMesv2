<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use App\Services\OrderCalculatorService;
use App\Services\QuoteCalculatorService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Workflow\Deliverys;
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
        ]);
    }

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
