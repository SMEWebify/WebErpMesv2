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
        $PaymentMethode = AccountingPaymentMethod::create($request->only('code',
                                                                        'label',
                                                                        'code_account'));
        return redirect()->route('accounting')->with('success', 'Successfully created payment method mode.');
    }

    /**
     * @param $request
     * @return View
     */
    public function update(UpdatePaymentMethodRequest $request)
    {
        if($request->default == 1) AccountingPaymentMethod::query()->update(['default' => 0]);
        $PaymentMethod = AccountingPaymentMethod::find($request->id);
        $PaymentMethod->label=$request->label;
        $PaymentMethod->code_account=$request->code_account;
        $PaymentMethod->default=$request->default;
        $PaymentMethod->save();
        return redirect()->route('accounting')->with('success', 'Successfully updated payment method mode.');
    }
}
