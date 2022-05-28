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
        $PaymentCondition = AccountingPaymentConditions::create($request->only('code',
                                                            'label',
                                                            'number_of_month',
                                                            'number_of_day',
                                                            'month_end'));
        return redirect()->route('accounting')->with('success', 'Successfully created payment condition mode.');
    }

    /**
     * @param $request
     * @return View
     */
    public function update(UpdatePaymentConditionRequest $request)
    {
        $PaymentCondition = AccountingPaymentConditions::find($request->id);
        $PaymentCondition->label=$request->label;
        $PaymentCondition->number_of_month=$request->number_of_month;
        $PaymentCondition->number_of_day=$request->number_of_day;
        $PaymentCondition->month_end=$request->month_end;
        $PaymentCondition->save();
        return redirect()->route('accounting')->with('success', 'Successfully updated payment condition mode.');
    }
}
