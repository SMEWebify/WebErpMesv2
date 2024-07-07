<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use App\Services\OrderCalculator;
use App\Services\QuoteCalculator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
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
        
        $QuoteCalculator = new QuoteCalculator($Quote);
        $totalPrice = $QuoteCalculator->getTotalPrice();
        $subPrice = $QuoteCalculator->getSubTotal();
        $vatPrice = $QuoteCalculator->getVatTotal();
        $TotalServiceProductTime = $QuoteCalculator->getTotalProductTimeByService();
        $TotalServiceSettingTime = $QuoteCalculator->getTotalSettingTimeByService();
        $TotalServiceCost = $QuoteCalculator->getTotalCostByService();
        $TotalServicePrice = $QuoteCalculator->getTotalPriceByService();
        
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
        
        $OrderCalculator = new OrderCalculator($Order);
        $totalPrice = $OrderCalculator->getTotalPrice();
        $subPrice = $OrderCalculator->getSubTotal();
        $vatPrice = $OrderCalculator->getVatTotal();
        $TotalServiceProductTime = $OrderCalculator->getTotalProductTimeByService();
        $TotalServiceSettingTime = $OrderCalculator->getTotalSettingTimeByService();
        $TotalServiceCost = $OrderCalculator->getTotalCostByService();
        $TotalServicePrice = $OrderCalculator->getTotalPriceByService();
        
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
}
