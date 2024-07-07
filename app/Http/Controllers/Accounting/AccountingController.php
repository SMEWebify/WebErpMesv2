<?php

namespace App\Http\Controllers\Accounting;

use Illuminate\Http\Request;
use App\Services\SelectDataService;
use App\Models\Accounting\AccountingVat;
use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingAllocation;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingPaymentConditions;

class AccountingController extends Controller
{
    protected $SelectDataService;

    public function __construct(SelectDataService $SelectDataService)
    {
        $this->SelectDataService = $SelectDataService;
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $Allocations = AccountingAllocation::All();
        $Deleverys = AccountingDelivery::All();
        $PaymentConditions = AccountingPaymentConditions::All();
        $PaymentMethods = AccountingPaymentMethod::All();
        $VATs = AccountingVat::All();
        $VATSelect = $this->SelectDataService->getVATSelect();

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
