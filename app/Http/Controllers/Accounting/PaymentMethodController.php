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
     * Store a newly created payment method in storage.
     *
     * @param \App\Http\Requests\Accounting\StorePaymentMethodRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StorePaymentMethodRequest $request)
    {
        $paymentMethod = AccountingPaymentMethod::create($request->validated());
        return redirect()->to(route('accounting') . '#PaymentChoice')
                        ->with('success', 'Successfully created payment method mode.');
    }

    /**
     * Update the specified payment method in storage.
     *
     * @param \App\Http\Requests\Accounting\UpdatePaymentMethodRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdatePaymentMethodRequest $request)
    {
        $paymentMethod = AccountingPaymentMethod::findOrFail($request->id);
        $paymentMethod->update($request->validated());

        // Set other payment methods to non-default if this one is marked default
        if ($request->default) {
            AccountingPaymentMethod::where('id', '!=', $paymentMethod->id)->update(['default' => 0]);
        }
        return redirect()->to(route('accounting') . '#PaymentChoice')
                        ->with('success', 'Successfully updated payment method mode.');
    }
}
