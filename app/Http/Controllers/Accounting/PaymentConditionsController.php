<?php

namespace App\Http\Controllers\Accounting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Accounting\AccountingPaymentConditions;
use App\Http\Requests\Accounting\StorePaymentConditionRequest;

class PaymentConditionsController extends Controller
{
    /**
     * @param Request $request
     * @return View
     */
    public function store(StorePaymentConditionRequest $request)
    {
        $PaymentCondition = AccountingPaymentConditions::create($request->only('code',
                                                            'label',
                                                            'number_of_month',
                                                            'number_of_day',
                                                            'month_end'));
        return redirect()->route('accounting')->with('success', 'Successfully created payment condition mode.');
    }
}
