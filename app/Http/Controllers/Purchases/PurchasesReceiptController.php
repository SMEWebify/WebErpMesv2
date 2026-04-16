<?php

namespace App\Http\Controllers\Purchases;

use Illuminate\Http\Request;
use App\Traits\NextPreviousTrait;
use App\Models\Purchases\Purchases;
use App\Models\Products\Products;
use App\Models\Accounting\AccountingVat;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use App\Services\CustomFieldService;
use App\Services\DocumentCodeGenerator;
use App\Services\PurchaseKPIService;
use App\Services\PurchaseReceiptService;
use App\Services\QualityNonConformityService;
use Illuminate\Support\Facades\Auth;
use App\Models\Products\StockLocation;
use App\Models\Purchases\PurchaseReceipt;
use App\Models\Quality\QualityNonConformity;
use App\Models\Products\StockLocationProducts;
use App\Models\Purchases\PurchaseLines;
use App\Models\Purchases\PurchaseReceiptLines;
use App\Http\Requests\Purchases\UpdatePurchaseRequest;
use App\Http\Requests\Purchases\UpdatePurchaseReceiptRequest;

class PurchasesReceiptController extends Controller
{
    use NextPreviousTrait;

    protected $SelectDataService;
    protected $purchaseKPIService;
    protected $customFieldService;
    protected $purchaseOrderService;
    protected $qualityNonConformityService;
    protected $documentCodeGenerator;
    protected $purchaseReceiptService;

    public function __construct(
            SelectDataService $SelectDataService,
            PurchaseKPIService $purchaseKPIService,
            CustomFieldService $customFieldService,
            QualityNonConformityService $qualityNonConformityService,
            DocumentCodeGenerator $documentCodeGenerator,
            PurchaseReceiptService $purchaseReceiptService,
        ){
        $this->SelectDataService = $SelectDataService;
        $this->purchaseKPIService = $purchaseKPIService;
        $this->customFieldService = $customFieldService;
        $this->qualityNonConformityService = $qualityNonConformityService;
        $this->documentCodeGenerator = $documentCodeGenerator;
        $this->purchaseReceiptService = $purchaseReceiptService;
    }
    
    
    /**
     * Display the waiting receipt view (React).
     */
    public function waintingReceipt()
    {
        $lastReceipt = PurchaseReceipt::latest()->first();
        $initialCode = $this->documentCodeGenerator->generateDocumentCode('purchase-receipt', $lastReceipt?->id ?? 0);

        $reactEndpoints = [
            'init'        => route('purchases.waiting.receipt.json.init'),
            'store'       => route('purchases.waiting.receipt.json.store'),
            'storeEmpty'  => route('purchases.waiting.receipt.json.store.empty'),
        ];

        $reactTrans = [
            'title'                => __('general_content.waiting_to_receipt_trans_key'),
            'sort_company'         => __('general_content.sort_companie_trans_key'),
            'select_company'       => __('general_content.select_company_trans_key'),
            'no_select_company'    => __('general_content.no_select_company_trans_key'),
            'external_id'          => __('general_content.external_id_trans_key'),
            'delivery_note_number' => __('general_content.delivery_note_number_trans_key'),
            'user'                 => __('general_content.user_management_trans_key'),
            'select_user'          => __('general_content.select_user_management_trans_key'),
            'new_receipt'          => __('general_content.new_receipt_document_trans_key'),
            'new_empty_receipt'    => __('general_content.new_empty_receipt_document_trans_key'),
            'order'                => __('general_content.order_trans_key'),
            'qty'                  => __('general_content.qty_trans_key'),
            'order_label'          => __('general_content.label_trans_key'),
            'label'                => __('general_content.label_trans_key'),
            'product'              => __('general_content.product_trans_key'),
            'purchase_order'       => __('general_content.purchase_order_trans_key'),
            'supplier'             => __('general_content.supplier_trans_key'),
            'description'          => __('general_content.description_trans_key'),
            'delivery_date'        => __('general_content.delivery_date_trans_key'),
            'action'               => __('general_content.action_trans_key'),
            'add_to_document'      => __('general_content.add_to_document_trans_key'),
            'no_data'              => __('general_content.no_data_trans_key'),
            'generic'              => __('general_content.generic_trans_key'),
            'view'                 => __('general_content.view_trans_key'),
            'loading'              => __('general_content.notif_loading_trans_key'),
            'error'                => __('general_content.error_trans_key') ?? 'Erreur',
            'no_lines_selected'    => __('general_content.no_lines_selected_trans_key') ?? 'Aucune ligne sélectionnée',
        ];

        return view('purchases/purchases-wainting-receipt', compact('reactEndpoints', 'reactTrans', 'initialCode'));
    }

    /**
     * JSON init — companies list, users list, initial code, waiting lines.
     */
    public function waitingReceiptInit(Request $request)
    {
        $companiesId = $request->get('companies_id', '');

        $companyIds = $this->purchaseReceiptService->getUniqueCompanyIdsWithOpenPurchaseLines();
        $companies  = $this->SelectDataService->getSupplier($companyIds);
        $users      = $this->SelectDataService->getUsers();

        $lastReceipt = PurchaseReceipt::latest()->first();
        $initialCode = $this->documentCodeGenerator->generateDocumentCode('purchase-receipt', $lastReceipt?->id ?? 0);

        $lines = $this->purchaseReceiptService->getPurchasesWaintingReceiptLines($companiesId);

        $mappedLines = $lines->map(function ($line) {
            $task      = $line->tasks;
            $orderLine = $task?->OrderLines;
            $order     = $orderLine?->order;

            return [
                'id'                => $line->id,
                'code'              => $line->code,
                'label'             => $line->label,
                'qty'               => $line->qty,
                'delivery_date'     => $line->delivery_date ? \Carbon\Carbon::parse($line->delivery_date)->format('Y-m-d') : null,
                'purchases_id'      => $line->purchases_id,
                'purchase_code'     => $line->purchase?->code,
                'purchase_url'      => $line->purchases_id ? route('purchases.show', ['id' => $line->purchases_id]) : null,
                'supplier_code'     => $line->purchase?->companie?->code,
                'supplier_label'    => $line->purchase?->companie?->label,
                'tasks_id'          => $line->tasks_id,
                'task_id'           => $task?->id,
                'task_label'        => $task?->label,
                'task_url'          => $task ? route('production.task.statu.id', ['id' => $task->id]) : null,
                'task_component_id' => $task?->component_id,
                'component_label'   => $task?->component_id ? optional($task->Component)->label : null,
                'product_id'        => $line->product_id,
                'product_url'       => $task?->component_id
                    ? route('products.show', ['id' => $task->component_id])
                    : ($line->product_id ? route('products.show', ['id' => $line->product_id]) : null),
                'order_id'          => $order?->id,
                'order_code'        => $order?->code,
                'order_url'         => $orderLine?->orders_id ? route('orders.show', ['id' => $orderLine->orders_id]) : null,
                'order_line_qty'    => $orderLine?->qty,
                'order_line_label'  => $orderLine?->label,
            ];
        });

        return response()->json([
            'companies'   => $companies->map(fn($c) => ['id' => $c->id, 'code' => $c->code, 'label' => $c->label]),
            'users'       => $users->map(fn($u) => ['id' => $u->id, 'name' => $u->name]),
            'initial_code' => $initialCode,
            'lines'       => $mappedLines,
        ]);
    }

    /**
     * JSON store — create a receipt from selected lines.
     */
    public function storeReceiptJson(Request $request)
    {
        $validated = $request->validate([
            'code'                 => 'required|unique:purchase_receipts',
            'label'                => 'required|string',
            'companies_id'         => 'required|exists:companies,id',
            'user_id'              => 'required|exists:users,id',
            'delivery_note_number' => 'nullable|string|max:255',
            'line_ids'             => 'required|array|min:1',
            'line_ids.*'           => 'integer|exists:purchase_lines,id',
        ]);

        // Transform line_ids into the format PurchaseReceiptService expects
        $data = [];
        foreach ($validated['line_ids'] as $id) {
            $data[$id] = ['purchase_line_id' => $id];
        }

        $receiptData = [
            'code'                 => $validated['code'],
            'label'                => $validated['label'],
            'companies_id'         => $validated['companies_id'],
            'delivery_note_number' => $validated['delivery_note_number'] ?? null,
            'user_id'              => $validated['user_id'],
        ];

        try {
            $receipt = $this->purchaseReceiptService->createPurchaseReceipt($data, $receiptData);
            return response()->json(['redirect' => route('purchase.receipts.show', ['id' => $receipt->id])]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * JSON store-empty — create an empty receipt (no lines).
     */
    public function storeEmptyReceiptJson(Request $request)
    {
        $validated = $request->validate([
            'code'                 => 'required|unique:purchase_receipts',
            'label'                => 'required|string',
            'companies_id'         => 'required|exists:companies,id',
            'user_id'              => 'required|exists:users,id',
            'delivery_note_number' => 'nullable|string|max:255',
        ]);

        try {
            $receipt = $this->purchaseReceiptService->createEmptyPurchaseReceipt($validated);
            return response()->json(['redirect' => route('purchase.receipts.show', ['id' => $receipt->id])]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Display a specific purchase receipt.
     *
     * @param PurchaseReceipt $id
     * @return \Illuminate\Contracts\View\View
     */
    public function showReceipt(PurchaseReceipt $id)
    {   
        
        $StockLocationList = StockLocation::all();
        $StockLocationProductList = StockLocationProducts::all();
        $userSelect = $this->SelectDataService->getUsers();
        $nonConformities = $this->SelectDataService->getQualityNonConformity();
        $productSelect = $this->SelectDataService->getProductsSelect();
        list($previousUrl, $nextUrl) = $this->getNextPrevious(new PurchaseReceipt(), $id->id);

        $averageReceptionDelay = PurchaseReceiptLines::join('purchase_lines', 'purchase_receipt_lines.purchase_line_id', '=', 'purchase_lines.id')
                                                    ->where('purchase_receipt_lines.purchase_receipt_id', $id->id) // Filtrer par bon de réception spécifique
                                                    ->selectRaw('AVG(DATEDIFF(purchase_receipt_lines.created_at, purchase_lines.created_at)) AS avg_reception_delay')
                                                    ->first();

        $reactEndpoints = [
            'lines'            => route('purchases.receipt.lines.json', ['id' => $id->id]),
            'inspection'       => route('purchase.receipts.lines.update', ['purchaseReceiptLine' => '__ID__']),
            'manualLine'       => route('purchase.receipts.lines.manual', ['id' => $id->id]),
            'storeNewStock'    => route('products.stockline.store.from.purchase.order'),
            'entryExistingStock' => route('products.stockline.entry.from.purchase.order'),
        ];

        return view('purchases/purchases-receipt-show', [
            'PurchaseReceipt'      => $id,
            'previousUrl'          => $previousUrl,
            'nextUrl'              => $nextUrl,
            'averageReceptionDelay' => $averageReceptionDelay->avg_reception_delay,
            'reactEndpoints'       => $reactEndpoints,
        ]);
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

    /**
     * Update a purchase receipt.
     *
     * @param \App\Http\Requests\Purchases\UpdatePurchaseReceiptRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePurchaseReceipt(UpdatePurchaseReceiptRequest $request)
    {
        $PurchaseReceipt = PurchaseReceipt::find($request->id);
        $PurchaseReceipt->label=$request->label;
        $PurchaseReceipt->statu=$request->statu;
        $PurchaseReceipt->delivery_note_number=$request->delivery_note_number;
        $PurchaseReceipt->comment=$request->comment;
        $PurchaseReceipt->save();
        
        return redirect()->route('purchase.receipts.show', ['id' =>  $PurchaseReceipt->id])->with('success', __('general_content.purchase_receipt_updated_success_trans_key'));
    }

    /**
     * Update the reception control of a purchase receipt.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id The ID of the purchase receipt.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateReceiptControl(Request $request, $id)
    {
        $purchaseReceipt = PurchaseReceipt::findOrFail($id);

        $purchaseReceipt->reception_controlled = 1;
        $purchaseReceipt->reception_control_date = now(); 
        $purchaseReceipt->reception_control_user_id = Auth::id(); 
        $purchaseReceipt->save();

        return redirect()->back()->with('success', __('general_content.inspection_update_success_trans_key'));
    }

    /**
     * Display the receipt index view with React data.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function receipt()
    {
        $countData   = $this->purchaseKPIService->getPurchaseReciepCountDataRate();
        $monthlyData = $this->purchaseKPIService->getPurchaseReceiptMonthlyRecap();

        $total      = $countData->sum('PurchaseReciepCountRate');
        $inProgress = $countData->where('statu', 1)->first()?->PurchaseReciepCountRate ?? 0;
        $stock      = $countData->where('statu', 2)->first()?->PurchaseReciepCountRate ?? 0;

        $reactKpi = [
            'total'       => (int) $total,
            'in_progress' => (int) $inProgress,
            'stock'       => (int) $stock,
        ];

        $reactChart = [
            'statusRate'   => $countData->map(fn ($item) => [
                'statu'                  => $item->statu,
                'PurchaseReciepCountRate' => $item->PurchaseReciepCountRate,
            ])->values(),
            'monthlyRecap' => $monthlyData->map(fn ($item) => [
                'month'        => $item->month,
                'receiptCount' => $item->receiptCount,
            ])->values(),
        ];

        $reactEndpoints = [
            'list' => route('purchases.receipt.json.list'),
        ];

        $reactTrans = [
            'total_receipts' => __('general_content.po_receipt_trans_key'),
            'in_progress'    => __('general_content.in_progress_trans_key'),
            'stock'          => __('general_content.stock_trans_key'),
            'search'         => __('general_content.search_trans_key'),
            'code'           => __('general_content.id_trans_key'),
            'label'          => __('general_content.label_trans_key'),
            'company'        => __('general_content.customer_trans_key'),
            'lines_count'    => __('general_content.lines_count_trans_key'),
            'receipt_note'   => __('general_content.po_receipt_note_trans_key'),
            'status'         => __('general_content.status_trans_key'),
            'created_at'     => __('general_content.created_at_trans_key'),
            'action'         => __('general_content.action_trans_key'),
            'no_data'        => __('general_content.no_data_trans_key'),
            'yes'            => __('general_content.yes_trans_key'),
            'no'             => __('general_content.no_trans_key'),
            'dashboard'      => __('general_content.dashboard_trans_key'),
            'receipt_list'   => __('general_content.po_receipt_trans_key'),
            'statistics'     => __('general_content.statistiques_trans_key'),
            'monthly_recap'  => __('general_content.monthly_recap_report_trans_key'),
            'view'           => __('general_content.view_trans_key'),
            'jan' => 'January',  'feb' => 'February', 'mar' => 'March',
            'apr' => 'April',    'may' => 'May',       'jun' => 'June',
            'jul' => 'July',     'aug' => 'August',    'sep' => 'September',
            'oct' => 'October',  'nov' => 'November',  'dec' => 'December',
            'locale'         => str_replace('_', '-', config('app.locale')),
            'currency'       => 'EUR',
        ];

        return view('purchases/purchases-receipt', compact('reactKpi', 'reactChart', 'reactEndpoints', 'reactTrans'));
    }

    /**
     * Return a paginated JSON list of purchase receipts for the React index.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function listJson(Request $request)
    {
        $search    = $request->get('search', '');
        $statuses  = array_filter(array_map('intval', (array) $request->get('statuses', [])));
        $sortField = $request->get('sort', 'created_at');
        $sortAsc   = $request->boolean('asc', false);

        $allowed = ['code', 'label', 'companies_id', 'created_at', 'statu'];
        if (!in_array($sortField, $allowed)) {
            $sortField = 'created_at';
        }

        $dir = $sortAsc ? 'asc' : 'desc';

        $query = PurchaseReceipt::withCount('PurchaseReceiptLines')
            ->with('companie:id,label,recept_controle')
            ->when($search, fn ($q) => $q->where('label', 'like', "%{$search}%"))
            ->when($statuses, fn ($q) => $q->whereIn('statu', $statuses));

        if ($sortField === 'companies_id') {
            $query->leftJoin('companies', 'purchase_receipts.companies_id', '=', 'companies.id')
                  ->orderBy('companies.label', $dir)
                  ->select('purchase_receipts.*');
        } else {
            $query->orderBy($sortField, $dir);
        }

        $items = $query->paginate(15);

        return response()->json([
            'data' => $items->map(fn ($receipt) => [
                'id'                   => $receipt->id,
                'code'                 => $receipt->code,
                'label'                => $receipt->label,
                'companie_label'       => $receipt->companie?->label ?? '—',
                'recept_controle'      => $receipt->companie?->recept_controle ?? 0,
                'lines_count'          => $receipt->purchase_receipt_lines_count,
                'reception_controlled' => $receipt->reception_controlled,
                'statu'                => $receipt->statu,
                'created_at'           => $receipt->created_at?->format('Y-m-d'),
                'url'                  => route('purchase.receipts.show', ['id' => $receipt->id]),
                'pdf_url'              => route('pdf.receipt', ['Document' => $receipt->id]),
            ]),
            'meta' => [
                'total'        => $items->total(),
                'per_page'     => $items->perPage(),
                'current_page' => $items->currentPage(),
                'last_page'    => $items->lastPage(),
            ],
        ]);
    }

    /**
     * JSON endpoint — return all lines of a purchase receipt with related data.
     */
    public function receiptLinesJson(PurchaseReceipt $id)
    {
        $lines = $id->PurchaseReceiptLines()
            ->with([
                'purchaseLines.purchase',
                'purchaseLines.tasks.OrderLines.order',
                'purchaseLines.tasks.Component',
                'inspector',
                'qualityNonConformity',
                'StockLocationProducts',
            ])
            ->get();

        $users           = $this->SelectDataService->getUsers();
        $nonConformities = $this->SelectDataService->getQualityNonConformity();
        $products        = $this->SelectDataService->getProductsSelect();
        $stockLocations  = StockLocation::with('Stocks')->get();
        $stockLocProds   = StockLocationProducts::with('StockLocation')->get();

        return response()->json([
            'lines'  => $lines->map(fn($l) => $this->serializeReceiptLine($l)),
            'select' => [
                'users'                   => $users->map(fn($u) => ['id' => $u->id, 'name' => $u->name]),
                'non_conformities'        => $nonConformities->map(fn($nc) => ['id' => $nc->id, 'code' => $nc->code]),
                'products'                => $products->map(fn($p) => ['id' => $p->id, 'code' => $p->code, 'label' => $p->label]),
                'stock_locations'         => $stockLocations->map(fn($sl) => [
                    'id'         => $sl->id,
                    'code'       => $sl->code,
                    'stock_code' => $sl->Stocks?->code,
                ]),
                'stock_location_products' => $stockLocProds->map(fn($slp) => [
                    'id'            => $slp->id,
                    'code'          => $slp->code,
                    'products_id'   => $slp->products_id,
                    'location_code' => $slp->StockLocation?->code,
                ]),
            ],
        ]);
    }

    /**
     * Serialize a PurchaseReceiptLine for JSON consumption.
     */
    private function serializeReceiptLine(PurchaseReceiptLines $line): array
    {
        $purchaseLine = $line->purchaseLines;
        $task         = $purchaseLine->tasks;
        $orderLine    = $task?->OrderLines;
        $order        = $orderLine?->order;
        $productId    = $task?->component_id ?? $purchaseLine->product_id ?? null;

        return [
            'id'                         => $line->id,
            'ordre'                      => $line->ordre,
            'receipt_qty'                => $line->receipt_qty,
            'accepted_qty'               => $line->accepted_qty,
            'rejected_qty'               => $line->rejected_qty,
            'inspection_result'          => $line->inspection_result,
            'inspection_date'            => $line->inspection_date?->format('Y-m-d'),
            'inspected_by'               => $line->inspected_by,
            'inspector_name'             => $line->inspector?->name,
            'quality_non_conformity_id'  => $line->quality_non_conformity_id,
            'nc_code'                    => $line->qualityNonConformity?->code,
            'stock_location_products_id' => $line->stock_location_products_id,
            'stock_code'                 => $line->StockLocationProducts?->code,
            'stock_url'                  => $line->stock_location_products_id
                ? route('products.stockline.show', ['id' => $line->stock_location_products_id])
                : null,
            'purchase_line_id'           => $line->purchase_line_id,
            'purchase_line_label'        => $purchaseLine->label,
            'purchase_line_qty'          => $purchaseLine->qty,
            'purchase_id'                => $purchaseLine->purchases_id,
            'purchase_code'              => $purchaseLine->purchase?->code,
            'purchase_url'               => $purchaseLine->purchases_id
                ? route('purchases.show', ['id' => $purchaseLine->purchases_id])
                : null,
            'product_id'                 => $productId,
            'product_url'                => $productId
                ? route('products.show', ['id' => $productId])
                : null,
            'selling_price'              => $purchaseLine->selling_price,
            'tasks_id'                   => $purchaseLine->tasks_id,
            'task_id'                    => $task?->id,
            'task_label'                 => $task?->label,
            'task_url'                   => $task
                ? route('production.task.statu.id', ['id' => $task->id])
                : null,
            'task_component_id'          => $task?->component_id,
            'component_label'            => $task?->component_id
                ? optional($task->Component)->label
                : null,
            'quality_required'           => $task ? $task->getQualityRequiredAttribute() : null,
            'order_id'                   => $order?->id,
            'order_code'                 => $order?->code,
            'order_url'                  => $orderLine?->orders_id
                ? route('orders.show', ['id' => $orderLine->orders_id])
                : null,
            'order_line_qty'             => $orderLine?->qty,
            'task_qty'                   => $task?->qty,
            'order_line_label'           => $orderLine?->label,
        ];
    }

    /**
     * Update inspection related data for a purchase receipt line.
     */
    public function updateLineInspection(Request $request, PurchaseReceiptLines $purchaseReceiptLine)
    {
        $validated = $request->validate([
            'inspected_by' => 'nullable|exists:users,id',
            'inspection_date' => 'nullable|date',
            'accepted_qty' => 'nullable|integer|min:0',
            'rejected_qty' => 'nullable|integer|min:0',
            'inspection_result' => 'nullable|string|max:255',
            'quality_non_conformity_id' => 'nullable|exists:quality_non_conformities,id',
            'create_non_conformity' => 'nullable|boolean',
            'new_nc_label' => 'nullable|string|max:255',
            'new_nc_comment' => 'nullable|string|max:1000',
        ]);

        $purchaseReceiptLine->loadMissing('purchaseLines.purchase', 'purchaseLines.tasks.OrderLines');

        $acceptedQty = array_key_exists('accepted_qty', $validated)
            ? (int) ($validated['accepted_qty'] ?? 0)
            : ($purchaseReceiptLine->accepted_qty ?? 0);

        $rejectedQty = array_key_exists('rejected_qty', $validated)
            ? (int) ($validated['rejected_qty'] ?? 0)
            : ($purchaseReceiptLine->rejected_qty ?? 0);

        $totalInspected = $acceptedQty + $rejectedQty;

        if ($totalInspected > $purchaseReceiptLine->receipt_qty) {
            $error = __('general_content.inspection_qty_error_trans_key', [
                'receipt' => $purchaseReceiptLine->receipt_qty,
            ]);
            if ($request->wantsJson()) {
                return response()->json(['errors' => ['accepted_qty' => [$error]]], 422);
            }
            return redirect()->back()->withErrors(['accepted_qty' => $error])->withInput();
        }

        if ($request->boolean('create_non_conformity') && empty($validated['quality_non_conformity_id'])) {
            $qualityNonConformity = $this->createQuickNonConformity(
                $purchaseReceiptLine,
                $validated['new_nc_label'] ?? null,
                $validated['new_nc_comment'] ?? null,
                $rejectedQty
            );

            $validated['quality_non_conformity_id'] = $qualityNonConformity->id;
        }

        $purchaseReceiptLine->update([
            'inspected_by' => $validated['inspected_by'] ?? null,
            'inspection_date' => $validated['inspection_date'] ?? null,
            'accepted_qty' => $acceptedQty,
            'rejected_qty' => $rejectedQty,
            'inspection_result' => $validated['inspection_result'] ?? null,
            'quality_non_conformity_id' => $validated['quality_non_conformity_id'] ?? null,
        ]);

        if ($request->wantsJson()) {
            $purchaseReceiptLine->load([
                'purchaseLines.purchase',
                'purchaseLines.tasks.OrderLines.order',
                'purchaseLines.tasks.Component',
                'inspector',
                'qualityNonConformity',
                'StockLocationProducts',
            ]);
            return response()->json(['line' => $this->serializeReceiptLine($purchaseReceiptLine)]);
        }

        return redirect()->back()->with('success', __('general_content.inspection_update_success_trans_key'));
    }

    public function storeManualReceiptLine(Request $request, PurchaseReceipt $id)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|integer|min:1',
        ]);

        $product = Products::findOrFail($validated['product_id']);
        $defaultAddress = CompaniesAddresses::getDefault(['companies_id' => $id->companies_id]);
        $defaultContact = CompaniesContacts::getDefault(['companies_id' => $id->companies_id]);
        $accountingVat = AccountingVat::getDefault();

        if (!$defaultAddress || !$defaultContact) {
            return redirect()->back()->with('error', 'No default settings fount for address or contact');
        }

        if (!$accountingVat) {
            return redirect()->back()->with('error', 'No default accounting VAT found');
        }

        $manualPurchaseCode = 'MANUAL-' . $id->code;
        $purchase = Purchases::firstOrCreate(
            ['code' => $manualPurchaseCode],
            [
                'label' => $manualPurchaseCode,
                'companies_id' => $id->companies_id,
                'companies_contacts_id' => $defaultContact->id,
                'companies_addresses_id' => $defaultAddress->id,
                'user_id' => Auth::id(),
            ]
        );

        $nextPurchaseOrdre = (int) PurchaseLines::where('purchases_id', $purchase->id)->max('ordre');
        $nextReceiptOrdre = (int) PurchaseReceiptLines::where('purchase_receipt_id', $id->id)->max('ordre');
        $qty = (int) $validated['qty'];
        $price = $product->purchased_price ?? 0;

        $purchaseLine = PurchaseLines::create([
            'purchases_id' => $purchase->id,
            'tasks_id' => 0,
            'ordre' => $nextPurchaseOrdre + 10,
            'code' => $product->code,
            'product_id' => $product->id,
            'label' => $product->label,
            'qty' => $qty,
            'selling_price' => $price,
            'discount' => 0,
            'unit_price_after_discount' => $price,
            'total_selling_price' => $price * $qty,
            'receipt_qty' => $qty,
            'methods_units_id' => $product->methods_units_id,
            'accounting_vats_id' => $accountingVat->id,
        ]);

        $receiptLine = PurchaseReceiptLines::create([
            'purchase_receipt_id' => $id->id,
            'purchase_line_id' => $purchaseLine->id,
            'ordre' => $nextReceiptOrdre + 10,
            'receipt_qty' => $qty,
        ]);

        $purchaseLines = PurchaseLines::where('purchases_id', $purchase->id)->get();
        $allReceived = $purchaseLines->every(function ($purchaseLineItem) {
            return $purchaseLineItem->receipt_qty >= $purchaseLineItem->qty;
        });

        $purchase->statu = $allReceived ? 4 : 3;
        $purchase->save();

        if ($request->wantsJson()) {
            $receiptLine->load([
                'purchaseLines.purchase',
                'purchaseLines.tasks.OrderLines.order',
                'purchaseLines.tasks.Component',
                'inspector',
                'qualityNonConformity',
                'StockLocationProducts',
            ]);
            return response()->json(['line' => $this->serializeReceiptLine($receiptLine)]);
        }

        return redirect()->back()->with('success', 'Successfully added manual receipt line');
    }

    protected function createQuickNonConformity(
        PurchaseReceiptLines $purchaseReceiptLine,
        ?string $label,
        ?string $comment,
        ?int $rejectedQty
    ): QualityNonConformity {
        $lastNonConformity = QualityNonConformity::latest('id')->first();
        $code = $this->documentCodeGenerator->generateDocumentCode('non-conformities', $lastNonConformity?->id ?? 0);
        $label = $label ?: $code;

        $data = [
            'code' => $code,
            'label' => $label,
            'statu' => 1,
            'user_id' => Auth::id(),
            'companie_id' => optional($purchaseReceiptLine->purchaseLines->purchase)->companies_id,
            'qty' => $rejectedQty ?? $purchaseReceiptLine->receipt_qty,
        ];

        if ($comment) {
            $data['failure_comment'] = $comment;
        }

        $orderLine = optional($purchaseReceiptLine->purchaseLines->tasks)->OrderLines;
        if ($orderLine) {
            $data['order_lines_id'] = $orderLine->id;
        }

        return $this->qualityNonConformityService->createNonConformity($data);
    }
}
