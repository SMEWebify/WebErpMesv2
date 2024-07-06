<?php

namespace App\Http\Controllers\Accounting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Http\Requests\Accounting\StorePaymentMethodRequest;
use App\Http\Requests\Accounting\UpdatePaymentMethodRequest;

class PaymentMethodController extends Controller
{
    /**
     * @param Request $request
     * @return View
     */
    public function store(StorePaymentMethodRequest $request)
    {
        $paymentMethod = AccountingPaymentMethod::create($request->validated());
        return redirect()->route('accounting')->with('success', 'Successfully created payment method mode.');
    }

    /**
     * @param $request
     * @return View
     */
    public function update(UpdatePaymentMethodRequest $request)
    {
        $paymentMethod = AccountingPaymentMethod::findOrFail($request->id);

        $paymentMethod->update($request->validated());

        // Set other payment methods to non-default if this one is marked default
        if ($request->default) {
            AccountingPaymentMethod::where('id', '!=', $paymentMethod->id)->update(['default' => 0]);
        }
        return redirect()->route('accounting')->with('success', 'Successfully updated payment method mode.');
    }
}
