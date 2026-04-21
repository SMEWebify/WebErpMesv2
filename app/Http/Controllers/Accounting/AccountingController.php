<?php

namespace App\Http\Controllers\Accounting;

use App\Models\Accounting\AccountingAllocation;
use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentConditions;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingVat;
use App\Models\Assets\Asset;
use App\Services\SelectDataService;
use App\Services\AccountingPeriodService;

class AccountingController extends Controller
{
    protected $SelectDataService;

    public function __construct(SelectDataService $SelectDataService)
    {
        $this->SelectDataService = $SelectDataService;
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function index()
    {
        return redirect()->route('accounting.paymentConditions');
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function paymentConditions()
    {
        return view('accounting.accounting-page', [
            'activeTab' => 'payment-conditions',
            'partial' => 'accounting.partials.payment-conditions',
            'PaymentConditions' => AccountingPaymentConditions::all(),
        ]);
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function paymentMethods()
    {
        return view('accounting.accounting-page', [
            'activeTab' => 'payment-methods',
            'partial' => 'accounting.partials.payment-methods',
            'PaymentMethods' => AccountingPaymentMethod::all(),
        ]);
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function vats()
    {
        return view('accounting.accounting-page', [
            'activeTab' => 'vat',
            'partial' => 'accounting.partials.vat',
            'VATs' => AccountingVat::all(),
        ]);
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function allocations()
    {
        return view('accounting.accounting-page', [
            'activeTab' => 'allocations',
            'partial' => 'accounting.partials.allocations',
            'Allocations' => AccountingAllocation::all(),
            'VATSelect' => $this->SelectDataService->getVATSelect(),
        ]);
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function deliveries()
    {
        return view('accounting.accounting-page', [
            'activeTab' => 'deliveries',
            'partial' => 'accounting.partials.deliveries',
            'Deleverys' => AccountingDelivery::all(),
        ]);
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function assets()
    {
        return view('accounting.accounting-page', [
            'activeTab' => 'assets',
            'partial' => 'accounting.partials.assets',
            'Assets' => Asset::paginate(10),
        ]);
    }

    public function periods()
    {
        $unlockUrlTemplate = route('accounting.periods.unlock', ['year' => '__year__', 'month' => '__month__']);

        return view('accounting.accounting-page', [
            'activeTab'   => 'periods',
            'partial'     => 'accounting.partials.periods',
            'endpoints'   => [
                'list'   => route('accounting.periods.json'),
                'lock'   => route('accounting.periods.lock'),
                'unlock' => $unlockUrlTemplate,
            ],
        ]);
    }
}
