<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workflow\Orders;
use App\Services\OrderCalculatorService;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingPaymentConditions;

use App\Http\Resources\OrderResource;

class OrderController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Workflow\Orders  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Orders $order)
    {
        return new OrderResource($order);
    }
}
