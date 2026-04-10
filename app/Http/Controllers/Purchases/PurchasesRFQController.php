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
     * Display the purchase quotation view.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function quotation()
    {    
        $factory = app('Factory');
        $currency = $factory->curency ?? 'EUR';
        $data['purchasesQuotationDataRate'] = $this->purchaseKPIService->getPurchaseQuotationDataRate();
        $totalPurchaseQuotationCount = PurchasesQuotation::count();
        $totalPurchaseQuotationLineCount = PurchaseQuotationLines::count();
        $totalPurchaseQuotationAmount = Number::currency(
            PurchaseQuotationLines::sum('total_price'),
            $currency,
            config('app.locale')
        );
                                                            
        return view('purchases/purchases-quotation', [
            'totalPurchaseQuotationCount' => $totalPurchaseQuotationCount,
            'totalPurchaseQuotationLineCount' => $totalPurchaseQuotationLineCount,
            'totalPurchaseQuotationAmount' => $totalPurchaseQuotationAmount,
        ])->with('data', $data);
    }

    /**
     * Display a specific purchase quotation.
     *
     * @param PurchasesQuotation $id
     * @return \Illuminate\Contracts\View\View
     */
    public function showQuotation(PurchasesQuotation $id)
    {   
        $CompanieSelect = $this->SelectDataService->getSupplier();
        $AddressSelect = $this->SelectDataService->getAddress($id->companies_id);
        $ContactSelect = $this->SelectDataService->getContact($id->companies_id);
        list($previousUrl, $nextUrl) = $this->getNextPrevious(new PurchasesQuotation(), $id->id);
                                    
        return view('purchases/purchases-quotation-show', [
            'PurchaseQuotation' => $id,
            'CompanieSelect' => $CompanieSelect,
            'AddressSelect' => $AddressSelect,
            'ContactSelect' => $ContactSelect,
            'previousUrl' =>  $previousUrl,
            'nextUrl' =>  $nextUrl,
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
}
