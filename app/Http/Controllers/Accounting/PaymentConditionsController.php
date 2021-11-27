<?php

namespace App\Http\Controllers\Accounting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Accounting\AccountingPaymentConditions;
use App\Http\Requests\Accounting\StorePaymentConditionRequest;

class PaymentConditionsController extends Controller
{
    //

    public function store(StorePaymentConditionRequest $request)
    {
        $PaymentCondition = AccountingPaymentConditions::create($request->only('CODE',
                                                            'LABEL',
                                                            'NUMBER_OF_MONTH',
                                                            'NUMBER_OF_DAY',
                                                            'MONTH_END'));
        return redirect()->route('accounting')->with('success', 'Successfully created payment condition mode.');
    }
}
