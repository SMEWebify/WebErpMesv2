<?php

namespace App\Http\Controllers\Accounting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Accounting\AccountingDelivery;
use App\Http\Requests\Accounting\StoreDeliveryRequest;
use App\Http\Requests\Accounting\UpdateDeliveryRequest;

class DeliveryController extends Controller
{
    /**
     * Store a newly created delivery in storage.
     *
     * @param \App\Http\Requests\Accounting\StoreDeliveryRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreDeliveryRequest $request)
    {
        $delivery = AccountingDelivery::create($request->validated());
        return redirect()->to(route('accounting') . '#Delevery')
                        ->with('success', 'Successfully created delevery mode.');
    }

    /**
     * Update the specified delivery in storage.
     *
     * @param \App\Http\Requests\Accounting\UpdateDeliveryRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateDeliveryRequest $request)
    {
        $delivery = AccountingDelivery::findOrFail($request->id);
        $delivery->update($request->validated());

        // Set other deliveries to non-default if this one is marked default
        if ($request->default) {
            AccountingDelivery::where('id', '!=', $delivery->id)->update(['default' => 0]);
        }

        return redirect()->to(route('accounting') . '#Delevery')
                        ->with('success', 'Successfully updated delevery mode.');
    }
}