<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Admin\Factory;
use App\Models\Workflow\Quotes;
use App\Services\QuoteCalculator;
use App\Http\Controllers\Controller;
use App\Models\Workflow\Orders;
use App\Services\OrderCalculator;
use League\CommonMark\Extension\SmartPunct\Quote;

class GuestController extends Controller
{
    /**
     * @return View
     */
    public function index()
    {
        return view('guest/guest');
    }

    /**
     * @return View
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
        $Factory = Factory::first();
        
        return view('guest/guest-quote-info', [
            'Quote' => $Quote,
            'Factory' => $Factory,
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
     * @return View
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
        $Factory = Factory::first();
        
        return view('guest/guest-order-info', [
            'Order' => $Order,
            'Factory' => $Factory,
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
