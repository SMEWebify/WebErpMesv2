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
       
        $Allocation = AccountingAllocation::create($request->only('ACCOUNT',
                                                                'LABEL', 
                                                                'vat_id',
                                                                'VAT_ACCOUNT', 
                                                                'CODE_ACCOUNT',
                                                                'TYPE_IMPUTATION'));

        return redirect()->route('accounting')->with('success', 'Successfully created allocation.');

    }
}
