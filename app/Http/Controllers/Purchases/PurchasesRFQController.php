<?php

namespace App\Http\Controllers\Purchases;

use Carbon\Carbon;
use App\Traits\NextPreviousTrait;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use App\Services\CustomFieldService;
use App\Services\PurchaseKPIService;
use App\Services\PurchaseQuotationService;
use App\Services\PurchaseOrderService;
use App\Models\Planning\Task;
use App\Models\Planning\Status;
use App\Models\Companies\Companies;
use App\Models\Workflow\OrderLines;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompaniesContacts;
use App\Models\Purchases\Purchases;
use App\Models\Purchases\PurchasesQuotation;
use App\Models\Purchases\PurchaseQuotationLines;
use App\Models\Purchases\PurchaseRfqGroup;
use App\Http\Requests\Purchases\UpdatePurchaseQuotationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;
use Illuminate\Support\Facades\DB;

class PurchasesRFQController extends Controller
{
    use NextPreviousTrait;

    protected $SelectDataService;
    protected $purchaseKPIService;
    protected $customFieldService;
    protected $purchaseQuotationService;
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
            PurchaseQuotationService $purchaseQuotationService,
            PurchaseOrderService $purchaseOrderService,
        ){
        $this->SelectDataService = $SelectDataService;
        $this->purchaseKPIService = $purchaseKPIService;
        $this->customFieldService = $customFieldService;
        $this->purchaseQuotationService = $purchaseQuotationService;
        $this->purchaseOrderService = $purchaseOrderService;
    }
    
    /**
     * Display the purchase request view (React).
     */
    public function request()
    {
        $reactProps = [
            'lastPurchaseCode'    => $this->purchaseOrderService->generatePurchaseCode(),
            'lastQuotationCode'   => $this->purchaseQuotationService->generatePurchasesQuotationCode(),
            'suppliers'           => $this->SelectDataService->getSupplier()->map(fn($c) => [
                'id'    => $c->id,
                'code'  => $c->code,
                'label' => $c->label,
            ])->values(),
        ];

        $reactEndpoints = [
            'tasks'     => route('purchases.request.tasks'),
            'store'     => route('purchases.request.store'),
            'exportCsv' => route('purchases.request.export-csv'),
        ];

        $reactTrans = [
            'document_type'      => __('general_content.document_type_trans_key'),
            'purchase_order'     => __('general_content.purchase_order_trans_key'),
            'purchase_quotation' => __('general_content.purchase_quotation_trans_key'),
            'select_document'    => __('general_content.select_document_trans_key'),
            'sort_supplier'      => __('general_content.sort_companie_trans_key'),
            'select_suppliers'   => __('general_content.select_suppliers_trans_key'),
            'select_company'     => __('general_content.select_company_trans_key'),
            'no_company'         => __('general_content.no_select_company_trans_key'),
            'external_id'        => __('general_content.external_id_trans_key'),
            'label'              => __('general_content.label_trans_key'),
            'new_purchase_doc'   => __('general_content.new_purchase_document_trans_key'),
            'order'              => __('general_content.order_trans_key'),
            'qty'                => __('general_content.qty_trans_key'),
            'order_label'        => __('general_content.order_trans_key') . ' ' . __('general_content.label_trans_key'),
            'task_label'         => __('general_content.label_trans_key'),
            'product'            => __('general_content.product_trans_key'),
            'service'            => __('general_content.service_trans_key'),
            'action'             => __('general_content.action_trans_key'),
            'add_to_document'    => __('general_content.add_to_document_trans_key'),
            'no_data'            => __('general_content.no_data_trans_key'),
            'view'               => __('general_content.view_trans_key'),
            'generic'            => __('general_content.generic_trans_key'),
            'export_csv'         => __('general_content.export_csv_trans_key'),
            'sheet_metal_need'   => __('general_content.sheet_metal_global_need_csv_trans_key'),
            'sheet_metal_hint'   => __('general_content.sheet_metal_global_need_csv_hint_trans_key'),
        ];

        return view('purchases/purchases-request', [
            'reactProps'     => $reactProps,
            'reactEndpoints' => $reactEndpoints,
            'reactTrans'     => $reactTrans,
        ]);
    }

    /**
     * Returns open purchase tasks (JSON) for the React form.
     * Optional ?company_id= filter.
     */
    public function requestTasks(Request $request)
    {
        $companyId = $request->get('company_id') ? (int) $request->get('company_id') : null;
        $sortField = in_array($request->get('sort'), ['label', 'id']) ? $request->get('sort') : 'id';
        $sortAsc   = $request->get('dir', 'asc') === 'asc';

        $firstStatus = Status::select('id')->orderBy('order')->first();

        $tasks = Task::orderBy('tasks.' . $sortField, $sortAsc ? 'asc' : 'desc')
            ->where('status_id', $firstStatus->id)
            ->whereNotNull('order_lines_id')
            ->whereIn('type', [2, 3, 4, 5, 6, 7])
            ->when($companyId, function ($query) use ($companyId) {
                $query->whereHas('Component.preferredSuppliers', fn($q) => $q->where('companies_id', $companyId));
            })
            ->with([
                'OrderLines.order:id,code,companies_id',
                'service:id,label,color,picture',
                'Component:id,label',
            ])
            ->get();

        return response()->json([
            'tasks' => $tasks->map(function ($task) {
                $orderLine = $task->OrderLines;
                $order     = $orderLine?->order;

                return [
                    'id'              => $task->id,
                    'label'           => $task->label,
                    'qty_required'    => $task->getQualityRequiredAttribute(),
                    'order_line_id'   => $task->order_lines_id,
                    'order_qty'       => $orderLine?->qty,
                    'order_label'     => $orderLine?->label,
                    'order_id'        => $order?->id,
                    'order_code'      => $order?->code,
                    'order_url'       => $order ? route('orders.show', ['id' => $order->id]) : null,
                    'component_id'    => $task->component_id,
                    'component_label' => $task->Component?->label,
                    'component_url'   => $task->component_id ? route('products.show', ['id' => $task->component_id]) : null,
                    'task_url'        => route('production.task.statu.id', ['id' => $task->id]),
                    'service_label'   => $task->service?->label,
                    'service_color'   => $task->service?->color,
                    'service_picture' => $task->service?->picture,
                ];
            })->values(),
        ]);
    }

    /**
     * Create a purchase order (PU) or purchase quotation (PQ) from the React form.
     */
    public function storePurchaseApi(Request $request)
    {
        abort_unless(auth()->check(), 403);

        $documentType = $request->input('document_type');

        if ($documentType === 'PU') {
            $validated = $request->validate([
                'document_type' => 'required|in:PU,PQ',
                'code'          => 'required|unique:purchases',
                'label'         => 'required',
                'companies_id'  => 'required|integer|min:1',
                'task_ids'      => 'array',
                'task_ids.*'    => 'integer',
            ]);

            $defaultSettings = [
                'AccountingVat'  => $this->purchaseOrderService->getAccountingVat(),
                'defaultAddress' => CompaniesAddresses::getDefault(['companies_id' => $validated['companies_id']]),
                'defaultContact' => CompaniesContacts::getDefault(['companies_id' => $validated['companies_id']]),
            ];

            foreach ($defaultSettings as $key => $setting) {
                if (is_null($setting)) {
                    return response()->json(['message' => 'No default settings for ' . str_replace('_', ' ', $key)], 422);
                }
            }

            $statusUpdate = $this->purchaseOrderService->getStatusUpdate();
            if (!$statusUpdate) {
                return response()->json(['message' => 'No status "Supplied" or "In progress" in kanban'], 422);
            }

            $purchase = $this->purchaseOrderService->createPurchaseOrder(
                $validated['companies_id'],
                $validated['code'],
                $validated['label'],
                $defaultSettings['defaultAddress']->id,
                $defaultSettings['defaultContact']->id,
            );

            if (!$purchase) {
                return response()->json(['message' => 'Failed to create purchase order'], 422);
            }

            if (!empty($validated['task_ids'])) {
                $data = collect($validated['task_ids'])->mapWithKeys(fn($id) => [$id => ['task_id' => $id]])->toArray();
                $this->purchaseOrderService->processPurchaseRequestLines($data, $purchase, $statusUpdate->id);
            }

            return response()->json(['redirect' => route('purchases.show', ['id' => $purchase->id])]);
        }

        if ($documentType === 'PQ') {
            $validated = $request->validate([
                'document_type'      => 'required|in:PU,PQ',
                'code'               => 'required|unique:purchase_rfq_groups,code',
                'label'              => 'required',
                'selected_companies' => 'required|array|min:1',
                'selected_companies.*' => 'integer|min:1',
                'task_ids'           => 'array',
                'task_ids.*'         => 'integer',
            ]);

            $statusUpdate = $this->purchaseQuotationService->getStatusUpdate();
            if (!$statusUpdate) {
                return response()->json(['message' => 'No status "RFQ in progress" or "Started" in kanban'], 422);
            }

            $rfqGroup = $this->purchaseQuotationService->createRfqGroup($validated['code'], $validated['label']);

            foreach ($validated['selected_companies'] as $companyId) {
                $company = Companies::find($companyId);
                if (!$company) {
                    return response()->json(['message' => 'Supplier not found'], 422);
                }

                $defaultSettings = [
                    'AccountingVat'  => $this->purchaseOrderService->getAccountingVat(),
                    'defaultAddress' => CompaniesAddresses::getDefault(['companies_id' => $companyId]),
                    'defaultContact' => CompaniesContacts::getDefault(['companies_id' => $companyId]),
                ];

                foreach ($defaultSettings as $key => $setting) {
                    if (is_null($setting)) {
                        return response()->json(['message' => 'No default settings for ' . str_replace('_', ' ', $key) . ' (' . $company->label . ')'], 422);
                    }
                }

                $quotationCode  = $this->purchaseQuotationService->generateGroupedQuotationCode($validated['code'], $company);
                $quotationLabel = $validated['label'] . ' - ' . $company->label;

                $quotation = $this->purchaseQuotationService->createPurchasesQuotation(
                    $companyId,
                    $quotationCode,
                    $quotationLabel,
                    $defaultSettings['defaultAddress']->id,
                    $defaultSettings['defaultContact']->id,
                    $rfqGroup->id,
                );

                if (!$quotation) {
                    return response()->json(['message' => 'Failed to create purchase quotation'], 422);
                }

                if (!empty($validated['task_ids'])) {
                    $data = collect($validated['task_ids'])->mapWithKeys(fn($id) => [$id => ['task_id' => $id]])->toArray();
                    $this->purchaseQuotationService->processPurchaseRequestLines($data, $quotation, $statusUpdate->id);
                }
            }

            $firstQuotation = PurchasesQuotation::where('rfq_group_id', $rfqGroup->id)->orderBy('id')->first();

            return response()->json(['redirect' => route('purchases.quotations.show', ['id' => $firstQuotation->id])]);
        }

        return response()->json(['message' => 'Invalid document type'], 422);
    }

    /**
     * Export open orders not started as CSV.
     */
    public function exportCsvApi()
    {
        $orderLines = OrderLines::query()
            ->whereIn('tasks_status', [1, 2])
            ->whereHas('order', fn($q) => $q->where('statu', 1))
            ->orderBy('order_lines.id')
            ->with(['order.companie', 'OrderLineDetails', 'Task.service'])
            ->get();

        $filename = 'purchase-request-open-orders-not-started-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($orderLines) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ExternalId', 'OF', 'Designation', 'Material', 'Thickness',
                'Quantity', 'Orientation', 'CutDeadline', 'DeliveryDeadline',
                'SymPath', 'DxfPath', 'Client', 'NextOperation',
            ], ';');

            foreach ($orderLines as $orderLine) {
                $order           = $orderLine->order;
                $orderLineDetails = $orderLine->OrderLineDetails;
                $nextTask        = $orderLine->Task->first();
                $service         = $nextTask?->service;
                $cutDeadline     = $nextTask?->due_date ? $nextTask->due_date->format('Y-m-d') : '';
                $deliveryDeadline = $orderLine->delivery_date
                    ? Carbon::parse($orderLine->delivery_date)->format('Y-m-d')
                    : '';

                fputcsv($handle, [
                    $orderLine->id,
                    $order?->code ?? '',
                    $orderLine->label ?? '',
                    $orderLineDetails?->material ?? '',
                    $orderLineDetails?->thickness ?? '',
                    $orderLine->qty,
                    0,
                    $cutDeadline,
                    $deliveryDeadline,
                    $orderLineDetails?->cam_file_path ?? '',
                    $orderLineDetails?->cad_file_path ?? '',
                    $order?->companie?->label ?? '',
                    $service?->label ?? '',
                ], ';');
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Display the purchase quotation view (React).
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function quotation()
    {
        $factory  = app('Factory');
        $currency = $factory->curency ?? 'EUR';

        $chartData = $this->purchaseKPIService->getPurchaseQuotationDataRate()
            ->map(fn($item) => [
                'statu' => $item->statu,
                'count' => $item->PurchaseQuotationCountRate,
            ])->values();

        $reactProps = [
            'kpi' => [
                'total_count'  => PurchasesQuotation::count(),
                'total_lines'  => PurchaseQuotationLines::count(),
                'total_amount' => Number::currency(
                    PurchaseQuotationLines::sum('total_price'),
                    $currency,
                    config('app.locale')
                ),
                'chart' => $chartData,
            ],
        ];

        $reactEndpoints = [
            'list' => route('purchases.quotation.api.list'),
            'kpi'  => route('purchases.quotation.api.kpi'),
        ];

        $reactTrans = [
            'statistics'       => __('general_content.statistiques_trans_key'),
            'purchase_quotation'=> __('general_content.purchase_quotation_trans_key'),
            'lines_count'      => __('general_content.lines_count_trans_key'),
            'total_price'      => __('general_content.total_price_trans_key'),
            'id'               => __('general_content.id_trans_key'),
            'label'            => __('general_content.label_trans_key'),
            'rfq_group'        => __('general_content.rfq_group_trans_key'),
            'supplier'         => __('general_content.customer_trans_key'),
            'status'           => __('general_content.status_trans_key'),
            'created_at'       => __('general_content.created_at_trans_key'),
            'action'           => __('general_content.action_trans_key'),
            'search'           => __('general_content.search_trans_key'),
            'no_data'          => __('general_content.no_data_trans_key'),
            'loading'          => __('general_content.loading_trans_key'),
            'compare_rfq'      => __('general_content.compare_rfq_trans_key'),
            'total'            => __('general_content.total_price_trans_key'),
        ];

        return view('purchases/purchases-quotation', [
            'reactProps'     => $reactProps,
            'reactEndpoints' => $reactEndpoints,
            'reactTrans'     => $reactTrans,
        ]);
    }

    /**
     * Display a specific purchase quotation (React).
     *
     * @param PurchasesQuotation $id
     * @return \Illuminate\Contracts\View\View
     */
    public function showQuotation(PurchasesQuotation $id)
    {
        $id->load([
            'companie',
            'contact',
            'adresse',
            'rfqGroup',
            'emailLogs',
            'PurchaseQuotationLines.tasks.OrderLines.order',
            'PurchaseQuotationLines.tasks.Component',
            'PurchaseQuotationLines.product',
        ]);

        $factory  = app('Factory');
        $currency = $factory->curency ?? 'EUR';

        list($previousUrl, $nextUrl) = $this->getNextPrevious(new PurchasesQuotation(), $id->id);

        $suppliers = $this->SelectDataService->getSupplier()->map(fn($c) => [
            'id'    => $c->id,
            'code'  => $c->code,
            'label' => $c->label,
        ])->values();

        $addresses = $this->SelectDataService->getAddress($id->companies_id)->map(fn($a) => [
            'id'    => $a->id,
            'label' => $a->label ?? ($a->address ?? ''),
        ])->values();

        $contacts = $this->SelectDataService->getContact($id->companies_id)->map(fn($c) => [
            'id'    => $c->id,
            'label' => $c->label ?? ($c->firstname . ' ' . $c->lastname),
        ])->values();

        $reactProps = [
            'quotation'  => $this->serializeQuotationFull($id, $currency),
            'suppliers'  => $suppliers,
            'addresses'  => $addresses,
            'contacts'   => $contacts,
            'currency'   => $currency,
        ];

        $reactEndpoints = [
            'update'      => route('purchases.quotation.api.update',       ['quotation' => $id->id]),
            'updateStatu' => route('purchases.quotation.api.statu',        ['quotation' => $id->id]),
            'storeLine'   => route('purchases.quotation.api.lines.store',  ['quotation' => $id->id]),
            'updateLine'  => route('purchases.quotation.api.lines.update', ['quotation' => $id->id, 'line' => '__ID__']),
            'products'    => route('purchases.quotation.api.products'),
            'companyData' => route('purchases.quotation.api.company-data'),
            'storeOrder'  => route('purchases.orders.store', ['id' => $id->id]),
        ];

        $reactTrans = [
            'purchase_quotation_info'  => __('general_content.purchase_quotation_info_trans_key'),
            'purchase_quotation_lines' => __('general_content.purchase_quotation_lines_trans_key'),
            'informations'             => __('general_content.informations_trans_key'),
            'options'                  => __('general_content.options_trans_key'),
            'external_id'              => __('general_content.external_id_trans_key'),
            'name_quote'               => __('general_content.name_quote_request_trans_key'),
            'supplier_info'            => __('general_content.supplier_info_trans_key'),
            'supplier'                 => __('general_content.supplier_trans_key'),
            'address'                  => __('general_content.address_trans_key'),
            'contact'                  => __('general_content.contact_trans_key'),
            'date_pay_info'            => __('general_content.date_pay_info_trans_key'),
            'validity_date'            => __('general_content.validity_date_trans_key'),
            'comment'                  => __('general_content.comment_trans_key'),
            'update'                   => __('general_content.update_trans_key'),
            'update_success'           => __('general_content.success_trans_key'),
            'error_generic'            => __('general_content.error_trans_key'),
            'update_valid'             => __('general_content.update_valide_trans_key'),
            'rfq_label'                => __('general_content.requests_for_quotation_list_trans_key'),
            'email'                    => __('general_content.email_trans_key'),
            'compare_rfq'              => __('general_content.compare_rfq_trans_key'),
            'duplicate'                => __('general_content.duplicate_purchase_quotation_trans_key'),
            'email_history'            => __('general_content.email_trans_key'),
            'select_supplier'          => __('general_content.select_company_trans_key'),
            'select_address'           => __('general_content.select_company_trans_key'),
            'select_contact'           => __('general_content.select_company_trans_key'),
            // Lines
            'sort'                     => __('general_content.sort_trans_key'),
            'product'                  => __('general_content.product_trans_key'),
            'select_product'           => __('general_content.select_product_trans_key'),
            'description'              => __('general_content.description_trans_key'),
            'qty'                      => __('general_content.qty_trans_key'),
            'price'                    => __('general_content.price_trans_key'),
            'total_price'              => __('general_content.total_price_trans_key'),
            'submit'                   => __('general_content.submit_trans_key'),
            'cancel'                   => __('general_content.cancel_trans_key'),
            'edit'                     => __('general_content.edit_trans_key'),
            'order'                    => __('general_content.order_trans_key'),
            'label'                    => __('general_content.label_trans_key'),
            'action'                   => __('general_content.action_trans_key'),
            'qty_accepted'             => __('general_content.qty_accepted_trans_key'),
            'qty_canceled'             => __('general_content.qty_canceled_trans_key'),
            'new_order'                => __('general_content.new_order_trans_key'),
            'generic'                  => __('general_content.generic_trans_key'),
            'view'                     => __('general_content.view_trans_key'),
            'no_data'                  => __('general_content.no_data_trans_key'),
            'info_statu'               => __('general_content.info_statu_trans_key'),
            'proposed_purchase_price'  => __('general_content.proposed_purchase_price_trans_key'),
        ];

        return view('purchases/purchases-quotation-show', [
            'PurchaseQuotation' => $id,
            'previousUrl'       => $previousUrl,
            'nextUrl'           => $nextUrl,
            'reactProps'        => $reactProps,
            'reactEndpoints'    => $reactEndpoints,
            'reactTrans'        => $reactTrans,
        ]);
    }

    /**
     * Display a comparison table for a RFQ group.
     *
     * @param PurchaseRfqGroup $group
     * @return \Illuminate\Contracts\View\View
     */
    public function compareQuotationGroup(PurchaseRfqGroup $group)
    {
        $group->load(['purchaseQuotations.companie', 'purchaseQuotations.PurchaseQuotationLines']);
        $quotations = $group->purchaseQuotations;

        $lineGroups = collect();
        foreach ($quotations as $quotation) {
            foreach ($quotation->PurchaseQuotationLines as $line) {
                $key = $line->product_id ? 'product-' . $line->product_id : 'line-' . $line->id;
                if (!$lineGroups->has($key)) {
                    $lineGroups->put($key, [
                        'label' => $line->label ?? $line->code ?? __('general_content.line_trans_key'),
                        'qty' => $line->qty_to_order,
                        'lines' => [],
                    ]);
                }

                $lineGroup = $lineGroups->get($key);
                $lineGroup['lines'][$quotation->id] = $line;
                $lineGroups->put($key, $lineGroup);
            }
        }

        $supplierTotals = $quotations->mapWithKeys(function ($quotation) {
            return [$quotation->id => $quotation->PurchaseQuotationLines->sum('total_price')];
        });

        return view('purchases/purchases-quotation-compare', [
            'rfqGroup' => $group,
            'quotations' => $quotations,
            'lineGroups' => $lineGroups->values(),
            'supplierTotals' => $supplierTotals,
        ]);
    }

    /**
     * Duplicate a purchase quotation with its lines.
     *
     * @param PurchasesQuotation $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function duplicateQuotation(PurchasesQuotation $id)
    {
        $id->load('PurchaseQuotationLines');
        $newCode = $this->purchaseQuotationService->generatePurchasesQuotationCode();
        $newLabel = $id->label . ' #duplicate' . $id->id;

        $newQuotation = DB::transaction(function () use ($id, $newCode, $newLabel) {
            $newQuotation = $this->purchaseQuotationService->createPurchasesQuotation(
                $id->companies_id,
                $newCode,
                $newLabel,
                $id->companies_contacts_id,
                $id->companies_addresses_id,
                $id->rfq_group_id
            );
            $newQuotation->delay = $id->delay;
            $newQuotation->statu = $id->statu;
            $newQuotation->comment = $id->comment;
            $newQuotation->save();

            foreach ($id->PurchaseQuotationLines as $line) {
                $newLine = $line->replicate();
                $newLine->purchases_quotation_id = $newQuotation->id;
                $newLine->save();
            }

            return $newQuotation;
        });

        return redirect()->route('purchases.quotations.show', ['id' =>  $newQuotation->id])
            ->with('success', 'Successfully duplicated purchase quotation');
    }

    /**
     * Update a purchase quotation.
     *
     * @param \App\Http\Requests\Purchases\UpdatePurchaseQuotationRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePurchaseQuotation(UpdatePurchaseQuotationRequest $request)
    {
        $PurchasesQuotation = PurchasesQuotation::find($request->id);
        $PurchasesQuotation->label=$request->label;
        $PurchasesQuotation->statu=$request->statu;
        $PurchasesQuotation->companies_id=$request->companies_id;
        $PurchasesQuotation->companies_contacts_id=$request->companies_contacts_id;
        $PurchasesQuotation->companies_addresses_id=$request->companies_addresses_id;
        $PurchasesQuotation->delay=$request->delay;
        $PurchasesQuotation->comment=$request->comment;
        $PurchasesQuotation->save();

        return redirect()->route('purchases.quotations.show', ['id' =>  $PurchasesQuotation->id])->with('success', 'Successfully updated purchase quotation');
    }

    // -------------------------------------------------------------------------
    // React API
    // -------------------------------------------------------------------------

    /**
     * API: paginated quotation list with search/sort.
     */
    public function apiQuotationList(Request $request)
    {
        abort_unless(auth()->check(), 403);

        $search    = $request->get('search', '');
        $sortField = in_array($request->get('sort'), ['code', 'label', 'companies_id', 'created_at'])
            ? $request->get('sort') : 'created_at';
        $sortAsc   = $request->get('dir', 'desc') === 'asc';

        $quotations = PurchasesQuotation::with(['companie', 'rfqGroup'])
            ->withCount('PurchaseQuotationLines')
            ->where('label', 'like', '%' . $search . '%')
            ->orderBy($sortField, $sortAsc ? 'asc' : 'desc')
            ->paginate(15);

        return response()->json([
            'data' => $quotations->map(fn($q) => $this->serializeQuotation($q)),
            'meta' => [
                'current_page'  => $quotations->currentPage(),
                'last_page'     => $quotations->lastPage(),
                'total'         => $quotations->total(),
                'per_page'      => $quotations->perPage(),
            ],
        ]);
    }

    /**
     * API: KPI stats for the list page.
     */
    public function apiQuotationKpi()
    {
        abort_unless(auth()->check(), 403);

        $factory  = app('Factory');
        $currency = $factory->curency ?? 'EUR';

        return response()->json([
            'total_count'  => PurchasesQuotation::count(),
            'total_lines'  => PurchaseQuotationLines::count(),
            'total_amount' => Number::currency(
                PurchaseQuotationLines::sum('total_price'),
                $currency,
                config('app.locale')
            ),
            'chart' => $this->purchaseKPIService->getPurchaseQuotationDataRate()
                ->map(fn($item) => [
                    'statu' => $item->statu,
                    'count' => $item->PurchaseQuotationCountRate,
                ])->values(),
        ]);
    }

    /**
     * API: single quotation with lines.
     */
    public function apiQuotationShow(PurchasesQuotation $quotation)
    {
        abort_unless(auth()->check(), 403);

        $quotation->load([
            'companie',
            'contact',
            'adresse',
            'rfqGroup',
            'emailLogs',
            'PurchaseQuotationLines.tasks.OrderLines.order',
            'PurchaseQuotationLines.tasks.Component',
            'PurchaseQuotationLines.product',
        ]);

        $factory  = app('Factory');
        $currency = $factory->curency ?? 'EUR';

        return response()->json([
            'quotation' => $this->serializeQuotationFull($quotation, $currency),
        ]);
    }

    /**
     * API: update quotation info.
     */
    public function apiQuotationUpdate(Request $request, PurchasesQuotation $quotation)
    {
        abort_unless(auth()->check(), 403);

        $validated = $request->validate([
            'label'                    => 'required|string',
            'companies_id'             => 'nullable|integer',
            'companies_contacts_id'    => 'nullable|integer',
            'companies_addresses_id'   => 'nullable|integer',
            'delay'                    => 'nullable|date',
            'comment'                  => 'nullable|string',
        ]);

        $quotation->fill($validated)->save();

        return response()->json(['success' => true]);
    }

    /**
     * API: update quotation status (arrow steps).
     */
    public function apiQuotationUpdateStatu(Request $request, PurchasesQuotation $quotation)
    {
        abort_unless(auth()->check(), 403);

        $validated = $request->validate([
            'statu' => 'required|integer|between:1,6',
        ]);

        $quotation->statu = $validated['statu'];
        $quotation->save();

        return response()->json(['statu' => $quotation->statu]);
    }

    /**
     * API: store a quotation line.
     */
    public function apiQuotationStoreLine(Request $request, PurchasesQuotation $quotation)
    {
        abort_unless(auth()->check(), 403);

        $validated = $request->validate([
            'ordre'        => 'required|numeric|gt:0',
            'label'        => 'required|string',
            'qty_to_order' => 'required|numeric|gt:0',
            'unit_price'   => 'required|numeric|min:0',
            'product_id'   => 'nullable|integer',
            'code'         => 'nullable|string',
        ]);

        $line = PurchaseQuotationLines::create([
            'purchases_quotation_id' => $quotation->id,
            'tasks_id'               => 0,
            'code'                   => $validated['code'] ?? '',
            'product_id'             => $validated['product_id'] ?? null,
            'label'                  => $validated['label'],
            'ordre'                  => $validated['ordre'],
            'qty_to_order'           => $validated['qty_to_order'],
            'unit_price'             => $validated['unit_price'],
            'total_price'            => $validated['unit_price'] * $validated['qty_to_order'],
        ]);

        $line->load(['product', 'tasks.OrderLines.order', 'tasks.Component']);

        $factory  = app('Factory');
        $currency = $factory->curency ?? 'EUR';

        return response()->json(['line' => $this->serializeLine($line, $currency)], 201);
    }

    /**
     * API: update a quotation line.
     */
    public function apiQuotationUpdateLine(Request $request, PurchasesQuotation $quotation, PurchaseQuotationLines $line)
    {
        abort_unless(auth()->check(), 403);
        abort_if($line->purchases_quotation_id !== $quotation->id, 404);

        $validated = $request->validate([
            'ordre'        => 'required|numeric|gt:0',
            'label'        => 'required|string',
            'qty_to_order' => 'required|numeric|gt:0',
            'unit_price'   => 'required|numeric|min:0',
            'product_id'   => 'nullable|integer',
            'code'         => 'nullable|string',
        ]);

        $line->fill([
            'ordre'        => $validated['ordre'],
            'label'        => $validated['label'],
            'qty_to_order' => $validated['qty_to_order'],
            'unit_price'   => $validated['unit_price'],
            'total_price'  => $validated['unit_price'] * $validated['qty_to_order'],
            'code'         => $validated['code'] ?? '',
            'product_id'   => $validated['product_id'] ?? null,
        ])->save();

        $line->load(['product', 'tasks.OrderLines.order', 'tasks.Component']);

        $factory  = app('Factory');
        $currency = $factory->curency ?? 'EUR';

        return response()->json(['line' => $this->serializeLine($line, $currency)]);
    }

    /**
     * API: products list (for line form autocomplete).
     */
    public function apiProducts()
    {
        abort_unless(auth()->check(), 403);

        $products = \App\Models\Products\Products::select('id', 'label', 'code', 'selling_price')
            ->orderBy('code')
            ->get()
            ->map(fn($p) => [
                'id'            => $p->id,
                'code'          => $p->code,
                'label'         => $p->label,
                'selling_price' => $p->selling_price,
            ]);

        return response()->json(['products' => $products]);
    }

    /**
     * API: addresses for a given company (used on supplier change).
     */
    public function apiCompanyAddresses(Request $request)
    {
        abort_unless(auth()->check(), 403);

        $companyId = (int) $request->get('company_id');
        $addresses = $this->SelectDataService->getAddress($companyId)->map(fn($a) => [
            'id'    => $a->id,
            'label' => $a->label ?? ($a->address ?? ''),
        ]);
        $contacts = $this->SelectDataService->getContact($companyId)->map(fn($c) => [
            'id'    => $c->id,
            'label' => $c->label ?? ($c->firstname . ' ' . $c->lastname),
        ]);

        return response()->json(['addresses' => $addresses, 'contacts' => $contacts]);
    }

    // -------------------------------------------------------------------------
    // Private serializers
    // -------------------------------------------------------------------------

    private function serializeQuotation(PurchasesQuotation $q): array
    {
        return [
            'id'                        => $q->id,
            'code'                      => $q->code,
            'label'                     => $q->label,
            'statu'                     => $q->statu,
            'companies_id'              => $q->companies_id,
            'companies_contacts_id'     => $q->companies_contacts_id,
            'companies_addresses_id'    => $q->companies_addresses_id,
            'created_at_human'          => $q->GetPrettyCreatedAttribute(),
            'lines_count'               => $q->purchase_quotation_lines_count ?? 0,
            'companie_label'            => $q->companie?->label ?? '',
            'companie_url'              => $q->companies_id ? route('companies.show', ['id' => $q->companies_id]) : null,
            'rfq_group_id'              => $q->rfq_group_id,
            'rfq_group_code'            => $q->rfqGroup?->code,
            'rfq_group_label'           => $q->rfqGroup?->label,
            'show_url'                  => route('purchases.quotations.show', ['id' => $q->id]),
            'pdf_url'                   => ($q->companies_contacts_id && $q->companies_addresses_id)
                ? route('pdf.purchase.quotation', ['Document' => $q->id]) : null,
            'compare_url'               => $q->rfq_group_id
                ? route('purchases.quotations.compare', ['group' => $q->rfq_group_id]) : null,
        ];
    }

    private function serializeQuotationFull(PurchasesQuotation $q, string $currency): array
    {
        return [
            'id'                        => $q->id,
            'code'                      => $q->code,
            'label'                     => $q->label,
            'statu'                     => $q->statu,
            'companies_id'              => $q->companies_id,
            'companies_contacts_id'     => $q->companies_contacts_id,
            'companies_addresses_id'    => $q->companies_addresses_id,
            'delay'                     => $q->delay,
            'comment'                   => $q->comment,
            'rfq_group_id'              => $q->rfq_group_id,
            'companie_label'            => $q->companie?->label ?? '',
            'pdf_url'                   => ($q->companies_contacts_id && $q->companies_addresses_id)
                ? route('pdf.purchase.quotation', ['Document' => $q->id]) : null,
            'compare_url'               => $q->rfq_group_id
                ? route('purchases.quotations.compare', ['group' => $q->rfq_group_id]) : null,
            'duplicate_url'             => route('purchases.quotations.duplicate', ['id' => $q->id]),
            'email_url'                 => (config('mail.default') && config('mail.from.address'))
                ? route('email.create', ['type' => 'purchase-quotation', 'id' => $q->id]) : null,
            'store_order_url'           => route('purchases.orders.store', ['id' => $q->id]),
            'email_logs'                => $q->emailLogs->map(fn($e) => [
                'id'         => $e->id,
                'subject'    => $e->subject ?? '',
                'created_at' => $e->created_at?->format('d/m/Y H:i') ?? '',
            ])->values()->toArray(),
            'lines' => $q->PurchaseQuotationLines->map(fn($l) => $this->serializeLine($l, $currency))->values()->toArray(),
        ];
    }

    private function serializeLine(PurchaseQuotationLines $l, string $currency): array
    {
        $task      = $l->tasks;
        $orderLine = $task?->OrderLines;
        $order     = $orderLine?->order;

        $formattedUnit  = Number::currency((float) $l->unit_price,  $currency, config('app.locale'));
        $formattedTotal = Number::currency((float) $l->total_price, $currency, config('app.locale'));

        return [
            'id'                  => $l->id,
            'ordre'               => $l->ordre,
            'code'                => $l->code ?? '',
            'label'               => $l->label ?? '',
            'product_id'          => $l->product_id,
            'product_code'        => $l->product?->code ?? '',
            'product_label'       => $l->product?->label ?? '',
            'product_url'         => $l->product_id ? route('products.show', ['id' => $l->product_id]) : null,
            'qty_to_order'        => $l->qty_to_order,
            'unit_price'          => $l->unit_price,
            'total_price'         => $l->total_price,
            'formatted_unit'      => $formattedUnit,
            'formatted_total'     => $formattedTotal,
            'qty_accepted'        => $l->qty_accepted,
            'canceled_qty'        => $l->canceled_qty,
            'task_id'             => $task?->id,
            'task_label'          => $task?->label ?? '',
            'task_url'            => $task ? route('production.task.statu.id', ['id' => $task->id]) : null,
            'component_id'        => $task?->component_id,
            'component_label'     => $task?->Component?->label ?? '',
            'component_url'       => $task?->component_id ? route('products.show', ['id' => $task->component_id]) : null,
            'order_id'            => $order?->id,
            'order_code'          => $order?->code ?? '',
            'order_url'           => $order ? route('orders.show', ['id' => $order->id]) : null,
            'order_line_qty'      => $orderLine?->qty,
            'task_qty'            => $task?->qty,
            'order_line_label'    => $orderLine?->label ?? '',
        ];
    }
}
