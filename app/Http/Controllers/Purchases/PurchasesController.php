<?php

namespace App\Http\Controllers\Purchases;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Number;
use App\Events\PurchaseCreated;
use App\Traits\NextPreviousTrait;
use App\Models\Purchases\Purchases;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use App\Services\CustomFieldService;
use App\Services\PurchaseKPIService;
use Illuminate\Support\Facades\Auth;
use App\Services\PurchaseOrderService;
use App\Models\Companies\CompaniesContacts;
use App\Services\PurchaseCalculatorService;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Purchases\PurchasesQuotation;
use App\Http\Requests\Purchases\StorePurchaseRequest;
use App\Http\Requests\Purchases\UpdatePurchaseRequest;

class PurchasesController extends Controller
{
    use NextPreviousTrait;

    protected $SelectDataService;
    protected $purchaseKPIService;
    protected $customFieldService;
    protected $purchaseOrderService;

    /**
     * Constructor to initialize services.
     *
     * @param SelectDataService $SelectDataService
     * @param PurchaseKPIService $purchaseKPIService
     * @param CustomFieldService $customFieldService
     * @param PurchaseOrderService $purchaseOrderService
     */
    public function __construct(
            SelectDataService $SelectDataService, 
            PurchaseKPIService $purchaseKPIService,
            CustomFieldService $customFieldService,
            PurchaseOrderService $purchaseOrderService,
        ){
        $this->SelectDataService = $SelectDataService;
        $this->purchaseKPIService = $purchaseKPIService;
        $this->customFieldService = $customFieldService;
        $this->purchaseOrderService = $purchaseOrderService;
    }

    /**
     * Display the purchase view with various data.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function purchase()
    {   
        $currentYear = Carbon::now()->format('Y');
        $factory     = app('Factory');
        $currency    = $factory->curency ?? 'EUR';
        $fiscal      = $factory->getCurrentFiscalYear();
        $data['purchasesDataRate']    = $this->purchaseKPIService->getPurchasesDataRate();
        $data['purchaseMonthlyRecap'] = $this->purchaseKPIService->getPurchaseMonthlyRecap($currentYear, $fiscal['start'], $fiscal['end']);

        $topRatedSuppliers = $this->purchaseKPIService->getTopRatedSuppliers();
        $sortedByAvgReceptionDelay = $this->purchaseKPIService->getAverageReceptionDelayBySupplier();
        $top5FastestSuppliers = $sortedByAvgReceptionDelay->take(5);
        $top5SlowestSuppliers = $sortedByAvgReceptionDelay->reverse()->take(5);

        $compositeIndicators = $this->purchaseKPIService->getSupplierCompositeIndicators();
        $suppliersToRequalify = $this->purchaseKPIService->getSuppliersToRequalify(30);

        $topProducts = $this->purchaseKPIService->getTopProducts();
        $averageAmount = Number::currency($this->purchaseKPIService->getAverageAmount(), $currency, config('app.locale'));
        $totalPurchaseLineCount = $this->purchaseKPIService->getTotalPurchaseCount();
        $totalPurchasesAmount = Number::currency($this->purchaseKPIService->getTotalPurchaseAmount(), $currency, config('app.locale'));

        $averageAmountRaw        = $this->purchaseKPIService->getAverageAmount();
        $totalPurchasesAmountRaw = $this->purchaseKPIService->getTotalPurchaseAmount();

        $reactKpi = [
            'averageAmount'         => round($averageAmountRaw, 2),
            'totalPurchasesAmount'  => round($totalPurchasesAmountRaw, 2),
            'totalPurchaseLineCount'=> $totalPurchaseLineCount,
        ];

        $reactChart = [
            'purchasesDataRate'    => $data['purchasesDataRate'],
            'purchaseMonthlyRecap' => $data['purchaseMonthlyRecap'],
            'fiscalYearStartMonth' => (int) ($factory->fiscal_year_start_month ?? 1),
        ];

        $reactTopSuppliers = $topRatedSuppliers->map(fn ($s) => [
            'label'      => $s->label,
            'avg_rating' => round((float) $s->averageRating(), 1),
        ])->values()->all();

        $reactFastestSuppliers = $top5FastestSuppliers->map(fn ($s) => [
            'supplier_name'      => $s->supplier_name,
            'avg_reception_delay'=> round((float) $s->avg_reception_delay, 1),
        ])->values()->all();

        $reactSlowestSuppliers = $top5SlowestSuppliers->map(fn ($s) => [
            'supplier_name'      => $s->supplier_name,
            'avg_reception_delay'=> round((float) $s->avg_reception_delay, 1),
        ])->values()->all();

        $reactCompositeIndicators = $compositeIndicators->map(fn ($s) => [
            'label'           => $s->label,
            'composite_score' => $s->composite_score,
            'next_review_at'  => optional(optional($s->latest_review)->next_review_at)->format('d/m/Y'),
        ])->values()->all();

        $reactSuppliersToRequalify = $suppliersToRequalify->map(fn ($s) => [
            'label'            => $s->label,
            'evaluation_status'=> $s->evaluation_status,
            'composite_score'  => $s->composite_score,
            'next_review_at'   => optional($s->next_review_at)->format('d/m/Y'),
        ])->values()->all();

        $reactEndpoints = [
            'list'         => route('purchases.json.list'),
            'store'        => route('purchases.json.store'),
            'selectData'   => route('purchases.json.select-data'),
            'addresses'    => route('purchases.json.addresses', ['companyId' => '__ID__']),
            'contacts'     => route('purchases.json.contacts',  ['companyId' => '__ID__']),
            'storeAddress' => route('purchases.json.address.store'),
            'storeContact' => route('purchases.json.contact.store'),
        ];

        $reactTrans = [
            'dashboard'              => __('general_content.dashboard_trans_key'),
            'purchases_list'         => __('general_content.purchase_list_trans_key'),
            'in_progress'            => __('general_content.in_progress_trans_key'),
            'ordered'                => __('general_content.ordered_trans_key'),
            'partly_received'        => __('general_content.partly_received_trans_key'),
            'received'               => __('general_content.rceived_trans_key'),
            'canceled'               => __('general_content.canceled_trans_key'),
            'average_purchase_price' => __('general_content.average_purchase_price_trans_key'),
            'total_price'            => __('general_content.total_price_trans_key'),
            'lines_count'            => __('general_content.lines_count_trans_key'),
            'top_rated_supplier'     => __('general_content.top_rated_supplier_trans_key'),
            'suppliers_fastest'      => __('general_content.suppliers_shortest_times_trans_key'),
            'suppliers_slowest'      => __('general_content.suppliers_longest_times_trans_key'),
            'supplier'               => __('general_content.supplier_trans_key'),
            'delivery_time'          => __('general_content.delevery_time_trans_key'),
            'purchases_by_status'    => __('general_content.statistiques_trans_key'),
            'monthly_recap'          => __('general_content.monthly_recap_report_trans_key'),
            'new_purchase'           => __('general_content.new_purchase_document_trans_key'),
            'search'                 => __('general_content.search_trans_key'),
            'code'                   => 'Code',
            'label'                  => __('general_content.label_trans_key'),
            'company'                => __('general_content.name_company_trans_key'),
            'contact'                => __('general_content.contact_trans_key'),
            'address'                => __('general_content.new_address_trans_key'),
            'status'                 => __('general_content.status_trans_key'),
            'lines'                  => __('general_content.purchase_lines_trans_key'),
            'created_at'             => __('general_content.created_at_trans_key'),
            'total_amount'           => __('general_content.total_trans_key'),
            'assignee'               => __('general_content.user_management_trans_key'),
            'comment'                => __('general_content.comment_trans_key'),
            'page'                   => 'Page',
            'purchases'              => __('general_content.purchase_list_trans_key'),
            'ordre'                  => __('general_content.ordre_trans_key'),
            'adress_label'           => __('general_content.adress_name_trans_key'),
            'adress'                 => __('general_content.adress_trans_key'),
            'postal_code'            => __('general_content.postal_code_trans_key'),
            'city'                   => __('general_content.city_trans_key'),
            'country'                => __('general_content.country_trans_key'),
            'phone'                  => __('general_content.phone_trans_key'),
            'email'                  => __('general_content.email_trans_key'),
            'civility'               => __('general_content.civility_trans_key'),
            'first_name'             => __('general_content.first_name_trans_key'),
            'name'                   => __('general_content.name_trans_key'),
            'function'               => __('general_content.function_trans_key'),
            'mobile'                 => __('general_content.mobile_phone_trans_key'),
            'new_address'            => __('general_content.new_address_trans_key'),
            'new_contact'            => __('general_content.new_companie_trans_key'),
            'save'                   => __('general_content.save_trans_key'),
            'saving'                 => __('general_content.saving_trans_key'),
            'cancel'                 => __('general_content.cancel_trans_key'),
            'no_results'             => __('general_content.no_results_trans_key'),
            'total'                  => __('general_content.total_trans_key'),
            'jan' => 'Janvier',  'feb' => 'Février',   'mar' => 'Mars',
            'apr' => 'Avril',    'may' => 'Mai',         'jun' => 'Juin',
            'jul' => 'Juillet',  'aug' => 'Août',        'sep' => 'Septembre',
            'oct' => 'Octobre',  'nov' => 'Novembre',   'dec' => 'Décembre',
            'purchase_forecast'  => 'Achats (année)',
            'purchase_last_year' => 'Achats (année préc.)',
            'currency'           => $currency,
            'locale'             => str_replace('_', '-', config('app.locale')),
            'composite_score'       => 'Score composite',
            'next_review_at'        => 'Prochaine révision',
            'evaluation_status'     => 'Statut',
            'suppliers_to_requalify'=> trans('general_content.suppliers_to_requalify_trans_key') !== 'general_content.suppliers_to_requalify_trans_key'
                ? __('general_content.suppliers_to_requalify_trans_key') : 'Fournisseurs à requalifier',
            'composite_indicators'  => trans('general_content.supplier_composite_indicator_trans_key') !== 'general_content.supplier_composite_indicator_trans_key'
                ? __('general_content.supplier_composite_indicator_trans_key') : 'Indicateurs composites fournisseurs',
            'most_purchased_products' => __('general_content.most_purchased_products_trans_key'),
            'product'               => __('general_content.product_trans_key'),
            'qty_total'             => __('general_content.qty_total_trans_key'),
        ];

        $reactTopProducts = $topProducts->map(fn ($p) => [
            'label'          => $p->label,
            'total_quantity' => $p->total_quantity,
        ])->values()->all();

        return view('purchases/purchases-index', compact(
            'reactKpi', 'reactChart', 'reactTopSuppliers', 'reactFastestSuppliers',
            'reactSlowestSuppliers', 'reactCompositeIndicators', 'reactSuppliersToRequalify',
            'reactEndpoints', 'reactTrans', 'reactTopProducts'
        ));
    }

    /**
     * Display a specific purchase.
     *
     * @param Purchases $id
     * @return \Illuminate\Contracts\View\View
     */
    public function showPurchase(Purchases $id)
    {   
        $CompanieSelect = $this->SelectDataService->getSupplier();
        $AddressSelect = $this->SelectDataService->getAddress($id->companies_id);
        $ContactSelect = $this->SelectDataService->getContact($id->companies_id);
        $PurchaseCalculatorService = new PurchaseCalculatorService($id);
        $totalPrice = $PurchaseCalculatorService->getTotalPrice();
        $subPrice = $PurchaseCalculatorService->getSubTotal();
        $vatPrice = $PurchaseCalculatorService->getVatTotal();
        list($previousUrl, $nextUrl) = $this->getNextPrevious(new Purchases(), $id->id);
        $CustomFields = $this->customFieldService->getCustomFieldsWithValues('purchase', $id->id);

        return view('purchases/purchases-show', [
            'Purchase' => $id,
            'CompanieSelect' => $CompanieSelect,
            'AddressSelect' => $AddressSelect,
            'ContactSelect' => $ContactSelect,
            'totalPrices' => $totalPrice,
            'subPrice' => $subPrice,
            'vatPrice' => $vatPrice,
            'previousUrl' =>  $previousUrl,
            'nextUrl' =>  $nextUrl,
            'CustomFields' => $CustomFields,
        ]);
    }

    /**
     * Prepare purchase data for storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|bool The prepared purchase data or false if defaults are missing.
     */
    protected function preparePurchaseData($request)
    {
        $defaultAddress = CompaniesAddresses::getDefault(['companies_id' => $request->companies_id]);
        $defaultContact = CompaniesContacts::getDefault(['companies_id' => $request->companies_id]);

        if (!$defaultAddress || !$defaultContact) {
            return false;
        }

        $purchaseData = $request->only('code', 'label', 'companies_id', 'user_id');
        $purchaseData['companies_addresses_id'] = $defaultAddress->id;
        $purchaseData['companies_contacts_id'] = $defaultContact->id;
        $purchaseData['user_id'] = Auth::id();

        return $purchaseData;
    }

    /**
     * Store a new bank purchase.
     *
     * @param \App\Http\Requests\Purchases\StorePurchaseRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeBankPurchase(StorePurchaseRequest  $request)
    {
        $purchaseData = $this->preparePurchaseData($request);

        if ($purchaseData === false) {
            return redirect()->back()->with('error', __('general_content.no_default_address_contact_vat_error_trans_key'));
        }
    
        $purchaseOrderCreated = Purchases::create($purchaseData);
    
        return redirect()->route('purchases.show', ['id' => $purchaseOrderCreated->id])
                            ->with('success', __('general_content.purchase_order_created_success_trans_key'));
    }

    /**
     * Store a new purchase order from a request for quotation (RFQ).
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id The ID of the purchase quotation.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storePurchaseOrderFromRFQ(Request $request, $id)
    { 
        if (!$request->PurchaseQuotationLine) {
            return redirect()->back()->withErrors(['msg' => 'no lines selected']);
        }
    
        $PurchasesQuotationData = PurchasesQuotation::findOrFail($id);
    
        $purchaseOrder = $this->purchaseOrderService->createPurchaseOrderFromQuotation($PurchasesQuotationData);
    
        if (!$purchaseOrder) {
            return redirect()->back()->withErrors(['msg' => 'Something went wrong (like no default settings for address, contact or accounting vat)']);
        }
    
        $statusUpdate = $this->purchaseOrderService->getStatusUpdate();
    
        if (!$statusUpdate) {
            return redirect()->back()->with('error', __('general_content.no_supplied_in_progress_status_error_trans_key'));
        }
    
        $this->purchaseOrderService->processPurchaseQuotationLines(
            $request->PurchaseQuotationLine,
            $request->PurchaseQuotationLineTaskid,
            $purchaseOrder,
            $statusUpdate->id,
            $request->PurchaseQuotationLinePrice,
        );

        // Déclencher l'événement PurchaseCreated
        event(new PurchaseCreated($PurchasesQuotationData));
    
        return redirect()->route('purchases.show', ['id' => $purchaseOrder->id])
                            ->with('success', __('general_content.purchase_order_created_success_trans_key'));
    }

    // -------------------------------------------------------------------------
    // JSON endpoints for the React PurchasesIndex component
    // -------------------------------------------------------------------------

    public function listJson(Request $request)
    {
        $search    = $request->get('search', '');
        $statuses  = array_filter(array_map('intval', (array) $request->get('statuses', [1, 2])));
        $sortField = $request->get('sort', 'created_at');
        $sortAsc   = $request->boolean('asc', false);
        $companyId = $request->get('company_id');

        $allowed = ['code', 'label', 'created_at', 'statu', 'companie', 'contact', 'purchase_lines_count', 'total_amount'];
        if (!in_array($sortField, $allowed)) {
            $sortField = 'created_at';
        }

        $dir      = $sortAsc ? 'asc' : 'desc';
        $totalSub = 'COALESCE((SELECT SUM(selling_price * qty * (1 - COALESCE(discount,0)/100)) FROM purchase_lines WHERE purchase_lines.purchases_id = purchases.id AND purchase_lines.deleted_at IS NULL), 0)';

        $query = Purchases::withCount('PurchaseLines')
            ->selectRaw("purchases.*, {$totalSub} as total_amount")
            ->with(['companie:id,label,code', 'contact:id,first_name,name'])
            ->when($search, fn ($q) => $q->where('label', 'like', '%'.$search.'%'))
            ->when($statuses, fn ($q) => $q->whereIn('statu', $statuses))
            ->when($companyId, fn ($q) => $q->where('companies_id', $companyId));

        match ($sortField) {
            'companie'             => $query->orderByRaw("(SELECT label FROM companies WHERE companies.id = purchases.companies_id) {$dir}"),
            'contact'              => $query->orderByRaw("(SELECT name FROM companies_contacts WHERE companies_contacts.id = purchases.companies_contacts_id) {$dir}"),
            'purchase_lines_count' => $query->orderBy('purchase_lines_count', $dir),
            'total_amount'         => $query->orderByRaw("{$totalSub} {$dir}"),
            default                => $query->orderBy($sortField, $dir),
        };

        $purchases = $query->paginate(15);

        return response()->json([
            'data' => $purchases->map(fn ($p) => [
                'id'                   => $p->id,
                'code'                 => $p->code,
                'label'                => $p->label,
                'statu'                => $p->statu,
                'created_at'           => $p->created_at?->format('d/m/Y'),
                'companie'             => $p->companie ? ['id' => $p->companie->id, 'label' => $p->companie->label] : null,
                'contact'              => $p->contact  ? ['id' => $p->contact->id,  'name'  => trim($p->contact->first_name.' '.$p->contact->name)] : null,
                'purchase_lines_count' => $p->purchase_lines_count,
                'total_amount'         => round((float) $p->total_amount, 2),
                'url'                  => route('purchases.show', ['id' => $p->id]),
            ]),
            'meta' => [
                'total'        => $purchases->total(),
                'per_page'     => $purchases->perPage(),
                'current_page' => $purchases->currentPage(),
                'last_page'    => $purchases->lastPage(),
            ],
        ]);
    }

    public function storeJson(Request $request)
    {
        abort_unless(auth()->check(), 403);

        $validated = $request->validate([
            'code'                   => 'required|unique:purchases',
            'label'                  => 'required',
            'companies_id'           => 'required|integer',
            'companies_contacts_id'  => 'required|integer',
            'companies_addresses_id' => 'required|integer',
            'comment'                => 'nullable|string',
        ]);

        $validated['user_id'] = Auth::id();

        $purchase = Purchases::create($validated);

        return response()->json([
            'redirect' => route('purchases.show', ['id' => $purchase->id]),
        ], 201);
    }

    public function selectDataJson()
    {
        $nextCode = $this->purchaseOrderService->generatePurchaseCode();

        return response()->json([
            'next_code' => $nextCode,
            'suppliers' => $this->SelectDataService->getSupplier()->map(fn ($c) => [
                'id'    => $c->id,
                'label' => $c->label ?? $c->last_name,
                'code'  => $c->code,
            ]),
            'users' => User::select('id', 'name')->get(),
        ]);
    }

    public function addressesJson(int $companyId)
    {
        return response()->json(
            CompaniesAddresses::select('id', 'label', 'adress')
                ->where('companies_id', $companyId)
                ->get()
        );
    }

    public function contactsJson(int $companyId)
    {
        return response()->json(
            CompaniesContacts::select('id', 'first_name', 'name')
                ->where('companies_id', $companyId)
                ->get()
                ->map(fn ($c) => ['id' => $c->id, 'name' => trim($c->first_name.' '.$c->name)])
        );
    }

    public function storeAddressJson(Request $request)
    {
        $validated = $request->validate([
            'companies_id' => 'required|integer',
            'label'        => 'required|string|max:255',
            'adress'       => 'nullable|string|max:500',
            'zipcode'      => 'nullable|string|max:20',
            'city'         => 'nullable|string|max:255',
            'country'      => 'nullable|string|max:255',
            'number'       => 'nullable|string|max:50',
            'mail'         => 'nullable|email|max:255',
            'ordre'        => 'nullable|integer',
        ]);

        $address = CompaniesAddresses::create($validated);
        return response()->json($address, 201);
    }

    public function storeContactJson(Request $request)
    {
        $validated = $request->validate([
            'companies_id' => 'required|integer',
            'first_name'   => 'nullable|string|max:255',
            'name'         => 'nullable|string|max:255',
            'civility'     => 'nullable|string|max:50',
            'function'     => 'nullable|string|max:255',
            'number'       => 'nullable|string|max:50',
            'mobile'       => 'nullable|string|max:50',
            'mail'         => 'nullable|email|max:255',
            'ordre'        => 'nullable|integer',
        ]);

        $contact = CompaniesContacts::create($validated);
        return response()->json([
            'id'   => $contact->id,
            'name' => trim(($contact->first_name ?? '').' '.($contact->name ?? '')),
        ], 201);
    }

    /**
     * Update a purchase order.
     *
     * @param \App\Http\Requests\Purchases\UpdatePurchaseRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePurchase(UpdatePurchaseRequest $request)
    {
        $Purchases = Purchases::find($request->id);
        $Purchases->label=$request->label;
        $Purchases->companies_id=$request->companies_id;
        $Purchases->companies_contacts_id=$request->companies_contacts_id;
        $Purchases->companies_addresses_id=$request->companies_addresses_id;
        $Purchases->comment=$request->comment;
        $Purchases->save();

        return redirect()->route('purchases.show', ['id' =>  $Purchases->id])->with('success', __('general_content.purchase_order_updated_success_trans_key'));
    }

    public function changeStatusJson(Request $request, $id)
    {
        try {
            Purchases::where('id', $id)->update(['statu' => (int) $request->input('statu')]);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Update failed'], 500);
        }
    }
}
