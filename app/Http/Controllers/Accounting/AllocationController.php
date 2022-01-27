<?php

namespace App\Http\Controllers\Accounting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Accounting\AccountingAllocation;
use App\Http\Requests\Accounting\StoreAllocationRequest;

class AllocationController extends Controller
{
    //
    public function store(StoreAllocationRequest $request)
    {
        $Allocation = AccountingAllocation::create($request->only('account',
                                                                'label', 
                                                                'vat_id',
                                                                'vat_account', 
                                                                'code_account',
                                                                'type_imputation'));

        return redirect()->route('accounting')->with('success', 'Successfully created allocation.');

    }
}
