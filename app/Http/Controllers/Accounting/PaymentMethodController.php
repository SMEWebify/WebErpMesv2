<?php

namespace App\Http\Controllers\Accounting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Http\Requests\Accounting\StorePaymentMethodRequest;

class PaymentMethodController extends Controller
{
    //
    public function store(StorePaymentMethodRequest $request)
    {
       
        $PaymentMethode = AccountingPaymentMethod::create($request->only('CODE',
                                                                        'LABEL',
                                                                        'CODE_ACCOUNT'));

        return redirect()->route('accounting')->with('success', 'Successfully created payment method mode.');

    }
}
