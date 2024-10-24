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
     * Store a newly created payment condition in storage.
     *
     * @param \App\Http\Requests\Accounting\StorePaymentConditionRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StorePaymentConditionRequest $request)
    {
        $paymentCondition = AccountingPaymentConditions::create($request->validated());
        $paymentCondition->month_end = $request->month_end ? 1 : 2;
        $paymentCondition->save();

        return redirect()->to(route('accounting') . '#PaymentCondition')
                        ->with('success', 'Successfully created payment condition mode.');
    }

    /**
     * Update the specified payment condition in storage.
     *
     * @param \App\Http\Requests\Accounting\UpdatePaymentConditionRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdatePaymentConditionRequest $request)
    {
        $paymentCondition = AccountingPaymentConditions::findOrFail($request->id);
        $paymentCondition->update($request->validated());
        $paymentCondition->month_end = $request->month_end_update ? 1 : 2;
        $paymentCondition->save();

        // Set other payment conditions to non-default if this one is marked default
        if ($request->default) {
            AccountingPaymentConditions::where('id', '!=', $paymentCondition->id)->update(['default' => 0]);
        }

        return redirect()->to(route('accounting') . '#PaymentCondition')
                        ->with('success', 'Successfully updated payment condition mode.');
    }
}