<?php

namespace App\Http\Controllers\Purchases;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Traits\NextPreviousTrait;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use App\Services\CustomFieldService;
use App\Services\PurchaseKPIService;
use App\Services\PurchaseOrderService;
use App\Models\Purchases\PurchaseInvoice;
use App\Http\Requests\Purchases\UpdatePurchaseInvoiceRequest;

class PurchasesInvoiceController extends Controller
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
     * Display the waiting invoice view.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function waintingInvoice()
    {    
        return view('purchases/purchases-wainting-invoice');
    }

    /**
     * Display a specific purchase invoice.
     *
     * @param PurchaseInvoice $id
     * @return \Illuminate\Contracts\View\View
     */
    public function showInvoice(PurchaseInvoice $id)
    {   
        list($previousUrl, $nextUrl) = $this->getNextPrevious(new PurchaseInvoice(), $id->id);

        return view('purchases/purchases-invoice-show', [
            'PurchaseInvoice' => $id,
            'previousUrl' =>  $previousUrl,
            'nextUrl' =>  $nextUrl,
        ]);
    }
    
    /**
     * Update a purchase invoice.
     *
     * @param \App\Http\Requests\Purchases\UpdatePurchaseInvoiceRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePurchaseInvoice(UpdatePurchaseInvoiceRequest $request)
    {
        $PurchaseInvoice = PurchaseInvoice::find($request->id);
        $PurchaseInvoice->label=$request->label;
        $PurchaseInvoice->statu=$request->statu;
        $PurchaseInvoice->comment=$request->comment;
        $PurchaseInvoice->save();
        
        return redirect()->route('purchase.invoices.show', ['id' =>  $PurchaseInvoice->id])->with('success', __('general_content.purchase_receipt_updated_success_trans_key'));
    }

    /**
     * Display the invoice view with data.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function invoice()
    {
        $currentYear = Carbon::now()->format('Y');

        $purchasesDataRate           = $this->purchaseKPIService->getPurchaseInvoiceDataRate();
        $purchaseInvoiceMonthlyRecap = $this->purchaseKPIService->getPurchaseInvoiceMonthlyRecap();

        $totalCount     = PurchaseInvoice::count();
        $toBePostedCount = PurchaseInvoice::where('statu', 2)->count();
        $closedCount    = PurchaseInvoice::where('statu', 3)->count();

        $reactKpi = [
            'totalCount'      => $totalCount,
            'toBePostedCount' => $toBePostedCount,
            'closedCount'     => $closedCount,
        ];

        $reactChart = [
            'purchaseInvoicesDataRate'       => $purchasesDataRate,
            'purchaseInvoiceMonthlyRecap'    => $purchaseInvoiceMonthlyRecap,
        ];

        $reactEndpoints = [
            'list' => route('purchases.invoice.json.list'),
        ];

        $reactTrans = [
            'total_invoices'      => __('general_content.total_trans_key'),
            'to_be_posted'        => __('general_content.to_be_posted_trans_key'),
            'closed'              => __('general_content.close_trans_key'),
            'in_progress'         => __('general_content.in_progress_trans_key'),
            'code'                => __('general_content.id_trans_key'),
            'label'               => __('general_content.label_trans_key'),
            'company'             => __('general_content.customer_trans_key'),
            'lines_count'         => __('general_content.lines_count_trans_key'),
            'status'              => __('general_content.status_trans_key'),
            'created_at'          => __('general_content.created_at_trans_key'),
            'action'              => __('general_content.action_trans_key'),
            'search'              => __('general_content.search_trans_key'),
            'no_data'             => __('general_content.no_data_trans_key'),
            'results'             => 'résultats',
            'view'                => __('general_content.view_trans_key'),
            'status_distribution' => __('general_content.statistiques_trans_key'),
            'monthly_recap'       => __('general_content.statistiques_trans_key'),
            'invoices'            => __('general_content.invoices_trans_key'),
            'jan' => 'Jan', 'feb' => 'Fév', 'mar' => 'Mar', 'apr' => 'Avr',
            'may' => 'Mai', 'jun' => 'Jun', 'jul' => 'Jul', 'aug' => 'Aoû',
            'sep' => 'Sep', 'oct' => 'Oct', 'nov' => 'Nov', 'dec' => 'Déc',
        ];

        return view('purchases/purchases-invoice', compact(
            'reactKpi',
            'reactChart',
            'reactEndpoints',
            'reactTrans',
        ));
    }

    /**
     * JSON endpoint — paginated list of purchase invoices for the React component.
     */
    public function listJson(Request $request)
    {
        $search    = $request->get('search', '');
        $statuses  = array_filter(array_map('intval', (array) $request->get('statuses', [])));
        $sortField = $request->get('sort', 'created_at');
        $sortAsc   = $request->boolean('asc', false);

        $allowed = ['code', 'label', 'companie', 'statu', 'created_at', 'lines_count'];
        if (!in_array($sortField, $allowed)) {
            $sortField = 'created_at';
        }

        $dir = $sortAsc ? 'asc' : 'desc';

        $query = PurchaseInvoice::withCount('PurchaseInvoiceLines as lines_count')
            ->with(['companie:id,label'])
            ->when($search, fn ($q) => $q->where('label', 'like', '%'.$search.'%'))
            ->when($statuses, fn ($q) => $q->whereIn('statu', $statuses));

        match ($sortField) {
            'companie'    => $query->orderByRaw("(SELECT label FROM companies WHERE companies.id = purchase_invoices.companies_id) {$dir}"),
            'lines_count' => $query->orderBy('lines_count', $dir),
            default       => $query->orderBy($sortField, $dir),
        };

        $invoices = $query->paginate(15);

        return response()->json([
            'data' => $invoices->map(fn ($inv) => [
                'id'          => $inv->id,
                'code'        => $inv->code,
                'label'       => $inv->label,
                'statu'       => $inv->statu,
                'created_at'  => $inv->created_at?->format('d/m/Y'),
                'companie'    => $inv->companie ? ['id' => $inv->companie->id, 'label' => $inv->companie->label] : null,
                'lines_count' => $inv->lines_count,
                'url'         => route('purchase.invoices.show', ['id' => $inv->id]),
            ]),
            'meta' => [
                'total'        => $invoices->total(),
                'per_page'     => $invoices->perPage(),
                'current_page' => $invoices->currentPage(),
                'last_page'    => $invoices->lastPage(),
            ],
        ]);
    }
}
