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
        $allocation = AccountingAllocation::create($request->validated());
        return redirect()->route('accounting')->with('success', 'Successfully created allocation.');
    }

    /**
     * @param $request
     * @return View
     */
    public function update(UpdateAllocationRequest $request)
    {
        $allocation = AccountingAllocation::findOrFail($request->id);
        $allocation->update($request->validated());
        return redirect()->route('accounting')->with('success', 'Successfully updated allocation.');
    }
}
