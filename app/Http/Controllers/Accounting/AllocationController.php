<?php

namespace App\Http\Controllers\Accounting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Accounting\AccountingAllocation;
use App\Http\Requests\Accounting\StoreAllocationRequest;
use App\Http\Requests\Accounting\UpdateAllocationRequest;

class AllocationController extends Controller
{
    /**
     * @param Request $request
     * @return View
     */
    public function store(StoreAllocationRequest $request)
    {
        $Allocation = AccountingAllocation::create($request->only('account',
                                                                'label', 
                                                                'accounting_vats_id',
                                                                'vat_account', 
                                                                'code_account',
                                                                'type_imputation'));
        return redirect()->route('accounting')->with('success', 'Successfully created allocation.');
    }

    /**
     * @param $request
     * @return View
     */
    public function update(UpdateAllocationRequest $request)
    {
        $Allocation = AccountingAllocation::find($request->id);
        $Allocation->label=$request->label;
        $Allocation->accounting_vats_id=$request->accounting_vats_id;
        $Allocation->vat_account=$request->vat_account;
        $Allocation->code_account=$request->code_account;
        $Allocation->type_imputation=$request->type_imputation;
        $Allocation->save();
        return redirect()->route('accounting')->with('success', 'Successfully updated allocation.');
    }
}
