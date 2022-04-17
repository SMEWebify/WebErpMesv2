<?php

namespace App\Http\Controllers\Accounting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Accounting\AccountingDelivery;
use App\Http\Requests\Accounting\StoreDeliveryRequest;

class DeliveryController extends Controller
{
    /**
     * @param Request $request
     * @return View
     */
    public function store(StoreDeliveryRequest $request)
    {
        $Delevery = AccountingDelivery::create($request->only('code','label'));
        return redirect()->route('accounting')->with('success', 'Successfully created delevery mode.');
    }
}
