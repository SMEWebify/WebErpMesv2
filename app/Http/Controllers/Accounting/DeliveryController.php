<?php

namespace App\Http\Controllers\Accounting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Accounting\AccountingDelivery;
use App\Http\Requests\Accounting\StoreDeliveryRequest;
use App\Http\Requests\Accounting\UpdateDeliveryRequest;

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

        /**
     * @param $request
     * @return View
     */
    public function update(UpdateDeliveryRequest $request)
    {
        if($request->default == 1) AccountingDelivery::query()->update(['default' => 0]);
        $AccountingDelivery = AccountingDelivery::find($request->id);
        $AccountingDelivery->label=$request->label;
        $AccountingDelivery->default=$request->default;
        $AccountingDelivery->save();
        return redirect()->route('accounting')->with('success', 'Successfully updated delevery mode.');
    }
}