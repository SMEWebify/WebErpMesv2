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
     * Store a newly created allocation in storage.
     *
     * @param \App\Http\Requests\Accounting\StoreAllocationRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreAllocationRequest $request)
    {
        $allocation = AccountingAllocation::create($request->validated());
        return redirect()->to(route('accounting') . '#AccountingAllocations')
                        ->with('success', 'Successfully created allocation.');
    }

    /**
     * Update the specified allocation in storage.
     *
     * @param \App\Http\Requests\Accounting\UpdateAllocationRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateAllocationRequest $request)
    {
        $allocation = AccountingAllocation::findOrFail($request->id);
        $allocation->update($request->validated());
        return redirect()->to(route('accounting') . '#AccountingAllocations')
                        ->with('success', 'Successfully updated allocation.');
    }
}
