<?php

namespace App\Http\Controllers\Accounting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Accounting\AccountingPaymentConditions;
use App\Http\Requests\Accounting\StorePaymentConditionRequest;
use App\Http\Requests\Accounting\UpdatePaymentConditionRequest;

class PaymentConditionsController extends Controller
{
    /**
     * @param Request $request
     * @return View
     */
    public function store(StorePaymentConditionRequest $request)
    {
        $paymentCondition = AccountingPaymentConditions::create($request->validated());
        return redirect()->route('accounting')->with('success', 'Successfully created payment condition mode.');
    }

    /**
     * @param $request
     * @return View
     */
    public function update(UpdatePaymentConditionRequest $request)
    {
        $paymentCondition = AccountingPaymentConditions::findOrFail($request->id);
        $paymentCondition->update($request->validated());

        // Set other payment conditions to non-default if this one is marked default
        if ($request->default) {
            AccountingPaymentConditions::where('id', '!=', $paymentCondition->id)->update(['default' => 0]);
        }

        return redirect()->route('accounting')->with('success', 'Successfully updated payment condition mode.');
    }
}