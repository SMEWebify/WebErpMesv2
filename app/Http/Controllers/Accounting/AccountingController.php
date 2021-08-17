<?php

namespace App\Http\Controllers\Accounting;

use Illuminate\Http\Request;
use App\Models\Accounting\AccountingVat;
use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingAllocation;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingPaymentConditions;

class AccountingController extends Controller
{
    public function index()
    {
        $Allocations = AccountingAllocation::All();
        $Deleverys = AccountingDelivery::All();
        $PaymentConditions = AccountingPaymentConditions::All();
        $PaymentMethods = AccountingPaymentMethod::All();
        $VATs = AccountingVat::All();
        $VATSelect = AccountingVat::select('id', 'LABEL')->orderBy('LABEL')->get();

        return view('accounting/accounting-index', [
            'Allocations' => $Allocations,
            'Deleverys' => $Deleverys,
            'PaymentConditions' => $PaymentConditions,
            'PaymentMethods' => $PaymentMethods,
            'VATs' => $VATs,
            'VATSelect' => $VATSelect,
        ]);
    }
}
