<?php

namespace App\Http\Controllers\Workflow;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Traits\NextPreviousTrait;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\DeliveryLines;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Packaging;
use App\Services\SelectDataService;
use App\Services\DeliveryService;
use App\Services\DeliveryLineService;
use App\Services\DeliveryDataService;
use App\Services\StockService;
use App\Services\DocumentCodeGenerator;
use App\Http\Controllers\Controller;
use App\Services\CustomFieldService;
use App\Services\DeliveryKPIService;
use App\Events\OrderLineUpdated;
use App\Models\Companies\CompanyDocumentDefault;
use App\Models\Products\StockLocationProducts;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\Workflow\StorePackagingRequest;
use App\Http\Requests\Workflow\UpdateDeliveryRequest;
use App\Http\Requests\Workflow\UpdatePackagingRequest;
use App\Jobs\CreateDeliverySerialNumbersJob;

class DeliverysController extends Controller
{
    use NextPreviousTrait;
    protected $SelectDataService;
    protected $deliveryKPIService;
    protected $customFieldService;
    protected $deliveryService;
    protected $deliveryLineService;
    protected $deliveryDataService;
    protected $stockService;
    protected $documentCodeGenerator;

    public function __construct(
        SelectDataService    $SelectDataService,
        DeliveryKPIService   $deliveryKPIService,
        CustomFieldService   $customFieldService,
        DeliveryService      $deliveryService,
        DeliveryLineService  $deliveryLineService,
        DeliveryDataService  $deliveryDataService,
        StockService         $stockService,
        DocumentCodeGenerator $documentCodeGenerator,
    ){
        $this->SelectDataService     = $SelectDataService;
        $this->deliveryKPIService    = $deliveryKPIService;
        $this->customFieldService    = $customFieldService;
        $this->deliveryService       = $deliveryService;
        $this->deliveryLineService   = $deliveryLineService;
        $this->deliveryDataService   = $deliveryDataService;
        $this->stockService          = $stockService;
        $this->documentCodeGenerator = $documentCodeGenerator;
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {    
        $CurentYear  = Carbon::now()->format('Y');
        $factory     = app('Factory');
        $fiscal      = $factory->getCurrentFiscalYear();
        $data['deliverysDataRate']    = $this->deliveryKPIService->getDeliveriesDataRate();
        $data['deliveryMonthlyRecap'] = $this->deliveryKPIService->getDeliveryMonthlyRecap($CurentYear, $fiscal['start'], $fiscal['end']);
        $data['fiscalYearStartMonth'] = (int) ($factory->fiscal_year_start_month ?? 1);

        return view('workflow/deliverys-index')->with('data', $data);
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function request()
    {
        $companyIds = $this->deliveryDataService->getUniqueCompanyIdsWithOpenOrderLines();

        $reactProps = [
            'code'           => $this->documentCodeGenerator->peekNextCode('delivery'),
            'userId'         => Auth::id(),
            'users'          => $this->SelectDataService->getUsers(),
            'companies'      => $this->SelectDataService->getCompanies($companyIds),
            'canManageStock' => auth()->user()->can('stock-lot-serial-management'),
        ];

        $reactEndpoints = [
            'companyData' => route('deliverys-request.company-data'),
            'store'       => route('deliverys-request.store'),
        ];

        $reactTrans = [
            'company'        => __('general_content.sort_companie_trans_key'),
            'external_id'    => __('general_content.external_id_trans_key'),
            'label'          => __('general_content.label_trans_key'),
            'user'           => __('general_content.user_management_trans_key'),
            'address'        => __('general_content.adress_name_trans_key'),
            'contact'        => __('general_content.contact_name_trans_key'),
            'remove_stock'   => __('general_content.remove_component_lines_stock_trans_key'),
            'create_serial'  => __('general_content.create_serial_number_trans_key'),
            'new_delivery'   => __('general_content.new_delivery_note_trans_key'),
            'order'          => __('general_content.order_trans_key'),
            'customer'       => __('general_content.customer_trans_key'),
            'task_status'    => __('general_content.tasks_status_trans_key'),
            'qty'            => __('general_content.qty_trans_key'),
            'scum_qty'       => __('general_content.scum_qty_trans_key'),
            'unit'           => __('general_content.unit_trans_key'),
            'price'          => __('general_content.price_trans_key'),
            'discount'       => __('general_content.discount_trans_key'),
            'vat'            => __('general_content.vat_trans_key'),
            'delivery_date'  => __('general_content.delivery_date_trans_key'),
            'action'         => __('general_content.action_trans_key'),
            'select_all'     => __('general_content.select_all_lines_trans_key'),
            'deselect_all'   => __('general_content.deselect_all_lines_trans_key'),
            'no_task'        => __('general_content.no_task_trans_key'),
            'created'        => __('general_content.created_trans_key'),
            'in_progress'    => __('general_content.in_progress_trans_key'),
            'finished'       => __('general_content.finished_task_trans_key'),
            'internal_order' => __('general_content.internal_order_trans_key'),
            'no_data'        => __('general_content.no_data_trans_key'),
            'select_company' => __('general_content.select_company_trans_key'),
            'select_user'    => __('general_content.select_user_management_trans_key'),
            'select_address' => __('general_content.select_address_trans_key'),
            'select_contact' => __('general_content.select_contact_trans_key'),
            'no_company'     => __('general_content.no_select_company_trans_key'),
            'no_address'     => __('general_content.no_address_trans_key'),
            'no_contact'     => __('general_content.no_contact_trans_key'),
            'no_lines'       => 'No lines selected',
            'add_to_note'    => 'Add',
        ];

        return view('workflow/deliverys-request', [
            'reactProps'     => $reactProps,
            'reactEndpoints' => $reactEndpoints,
            'reactTrans'     => $reactTrans,
        ]);
    }

    /**
     * Returns addresses, contacts and open order lines for a given company.
     */
    public function requestCompanyData(Request $request)
    {
        $companyId = $request->get('company_id') ? (int) $request->get('company_id') : null;

        $addresses = $this->SelectDataService->getAddress($companyId);
        $contacts  = $this->SelectDataService->getContact($companyId);
        $lines     = $this->deliveryDataService->getDeliveryRequestsLines($companyId, 'label', true);

        $lines->load(['order.companie:id,label', 'Unit:id,label', 'VAT:id,label']);

        $defaults = $companyId ? CompanyDocumentDefault::forCompany($companyId)['delivery'] : ['contact_id' => null, 'address_id' => null];

        $defaultAddressId = $defaults['address_id'] && $addresses->contains('id', $defaults['address_id'])
            ? $defaults['address_id']
            : $addresses->first()?->id;

        $defaultContactId = $defaults['contact_id'] && $contacts->contains('id', $defaults['contact_id'])
            ? $defaults['contact_id']
            : $contacts->first()?->id;

        return response()->json([
            'default_address_id' => $defaultAddressId,
            'default_contact_id' => $defaultContactId,
            'addresses' => $addresses->map(fn($a) => [
                'id'     => $a->id,
                'label'  => $a->label,
                'adress' => $a->adress,
            ]),
            'contacts' => $contacts->map(fn($c) => [
                'id'         => $c->id,
                'first_name' => $c->first_name,
                'name'       => $c->name,
            ]),
            'lines' => $lines->map(fn($l) => [
                'id'                      => $l->id,
                'code'                    => $l->code,
                'label'                   => $l->label,
                'tasks_status'            => $l->tasks_status,
                'delivered_remaining_qty' => $l->delivered_remaining_qty,
                'selling_price'           => $l->selling_price,
                'discount'                => $l->discount,
                'delivery_date'           => $l->delivery_date,
                'unit_label'              => $l->Unit?->label,
                'vat_label'               => $l->VAT?->label,
                'order_id'                => $l->order?->id,
                'order_code'              => $l->order?->code,
                'order_type'              => $l->order?->type,
                'order_url'               => $l->order ? route('orders.show', ['id' => $l->order->id]) : null,
                'companie_id'             => $l->order?->companies_id,
                'companie_label'          => $l->order?->companie?->label,
                'companie_url'            => $l->order?->companies_id ? route('companies.show', ['id' => $l->order->companies_id]) : null,
            ]),
        ]);
    }

    /**
     * Creates a delivery note from the React form submission.
     */
    public function storeDeliveryNoteApi(Request $request)
    {
        abort_unless(auth()->check(), 403);

        $validated = $request->validate([
            'code'                   => 'required|unique:deliverys',
            'label'                  => 'required',
            'companies_id'           => 'required|integer|min:1',
            'companies_addresses_id' => 'required|integer|min:1',
            'companies_contacts_id'  => 'required|integer|min:1',
            'user_id'                => 'required|integer|min:1',
            'lines'                  => 'required|array|min:1',
            'lines.*.order_line_id'  => 'required|integer',
            'lines.*.qty'            => 'required|numeric|min:0.01',
            'remove_from_stock'      => 'boolean',
            'create_serial_number'   => 'boolean',
        ]);

        $removeFromStock = $validated['remove_from_stock'] ?? false;

        $delivery = DB::transaction(function () use ($validated, $removeFromStock) {
            $delivery = $this->deliveryService->createDelivery(
                $validated['code'],
                $validated['label'],
                $validated['companies_id'],
                $validated['companies_addresses_id'],
                $validated['companies_contacts_id'],
                $validated['user_id'],
            );

            $ordre = 10;
            foreach ($validated['lines'] as $line) {
                $orderLineId = $line['order_line_id'];
                $qty         = $line['qty'];

                $this->deliveryLineService->createDeliveryLine($delivery, $orderLineId, $ordre, $qty);
                $this->updateOrderLineAfterDelivery($orderLineId, $qty);
                $this->applyStockMovement($orderLineId, $qty, $removeFromStock);

                $ordre += 10;
            }

            return $delivery;
        });

        if ($validated['create_serial_number'] ?? false) {
            CreateDeliverySerialNumbersJob::dispatch($delivery->id);
        }

        return response()->json([
            'redirect' => route('deliverys.show', ['id' => $delivery->id]),
        ]);
    }

    private function updateOrderLineAfterDelivery(int $orderLineId, float $qty): void
    {
        $orderLine = OrderLines::lockForUpdate()->find($orderLineId);

        if ($qty > $orderLine->delivered_remaining_qty) {
            throw ValidationException::withMessages([
                'lines' => "Quantité livrée ({$qty}) dépasse le restant à livrer ({$orderLine->delivered_remaining_qty}) pour la ligne #{$orderLineId}.",
            ]);
        }

        $orderLine->delivered_qty           += $qty;
        $orderLine->delivered_remaining_qty -= $qty;
        $orderLine->delivery_status          = ($orderLine->delivered_remaining_qty <= 0) ? 3 : 2;
        $orderLine->save();
        event(new OrderLineUpdated($orderLine));
    }

    private function applyStockMovement(int $orderLineId, float $qty, bool $removeFromStock): void
    {
        if (!$removeFromStock) return;

        $orderLine = OrderLines::find($orderLineId);

        if (!$orderLine->product_id || $orderLine->Task()->exists()) return;

        $quantityRemaining    = $qty;
        $stockLocationProducts = StockLocationProducts::where('products_id', $orderLine->product_id)->get();

        foreach ($stockLocationProducts as $stock) {
            $quantityToWithdraw = min($stock->getCurrentStockMove(), $quantityRemaining);

            if ($quantityToWithdraw != 0) {
                $this->stockService->createStockMove([
                    'user_id'                    => Auth::id(),
                    'qty'                        => $quantityToWithdraw,
                    'stock_location_products_id' => $stock->id,
                    'order_line_id'              => $orderLine->id,
                    'typ_move'                   => 9,
                ]);
            }

            $quantityRemaining -= $quantityToWithdraw;
            if ($quantityRemaining <= 0) break;
        }

        if ($quantityRemaining > 0) {
            throw ValidationException::withMessages([
                'stock' => "Stock insuffisant pour la ligne #{$orderLineId} : {$quantityRemaining} unité(s) manquante(s). Livraison annulée.",
            ]);
        }
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\View\View
     */
    public function show(Deliverys $id)
    {
        list($previousUrl, $nextUrl) = $this->getNextPrevious(new Deliverys(), $id->id);

        $id->load(['returns.qualityNonConformity', 'QualityNonConformity']);

        $PruchasesSelect = $this->SelectDataService->getPurchases();
        $CustomFields = $this->customFieldService->getCustomFieldsWithValues('delivery', $id->id);
        $allDelivered = $id->DeliveryLines->every(function($line) {
            return $line->invoice_status == 4;
        });

        $companies = $this->SelectDataService->getCompanies();
        $addresses = $this->SelectDataService->getAddress($id->companies_id);
        $contacts  = $this->SelectDataService->getContact($id->companies_id);

        return view('workflow/deliverys-show', [
            'Delivery'        => $id,
            'previousUrl'     => $previousUrl,
            'nextUrl'         => $nextUrl,
            'CustomFields'    => $CustomFields,
            'allDelivered'    => $allDelivered,
            'PruchasesSelect' => $PruchasesSelect,
            'companies'       => $companies,
            'addresses'       => $addresses,
            'contacts'        => $contacts,
            'companyAcUrl'    => route('deliverys.company-ac'),
            'nonConformities' => \App\Models\Quality\QualityNonConformity::select('id', 'code')->orderBy('code')->get(),
        ]);
    }
    
    public function changeStatusJson(Request $request, $id)
    {
        $delivery = Deliverys::findOrFail($id);
        $delivery->statu = (int) $request->input('statu');
        $delivery->save();

        return response()->json(['ok' => true]);
    }

    public function update(UpdateDeliveryRequest $request)
    {
        $Delivery = Deliverys::find($request->id);
        $Delivery->label                  = $request->label;
        $Delivery->purchases_id           = $request->purchases_id;
        $Delivery->tracking_number        = $request->tracking_number;
        $Delivery->comment                = $request->comment;
        $Delivery->customer_reference     = $request->customer_reference;
        $Delivery->companies_id           = $request->companies_id;
        $Delivery->companies_addresses_id = $request->companies_addresses_id;
        $Delivery->companies_contacts_id  = $request->companies_contacts_id;
        $Delivery->save();

        return redirect()->route('deliverys.show', ['id' =>  $Delivery->id])->with('success', 'Successfully updated Delivery');
    }

    /**
     * Store a newly created packaging in storage.
     *
     * @param StorePackagingRequest $request
     * @param Deliverys $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function packagingsStore(StorePackagingRequest $request, Deliverys $id)
    {
        // Création et sauvegarde du packaging
        Packaging::create(
            $request->only([
                'deliverys_id',
                'code',
                'type',
                'status',
                'gross_weight',
                'net_weight',
                'length',
                'width',
                'height',
                'comment',
                'packing_date',
                'loaded_date',
                'load_comment',
            ]) + [
                'user_id' => Auth::id(),
            ]
        );

        // Redirection avec un message de succès
        return redirect()->route('deliverys.show', ['id' => $id->id])->with('success', 'Successfully add packaging');
    }

    // -------------------------------------------------------------------------
    // JSON endpoints for the React DeliverysIndex component
    // -------------------------------------------------------------------------

    public function listJson(Request $request)
    {
        $search          = $request->get('search', '');
        $statuses        = array_filter(array_map('intval', (array) $request->get('statuses', [])));
        $invoiceStatuses = array_filter(array_map('intval', (array) $request->get('invoice_statuses', [])));
        $sortField       = $request->get('sort', 'created_at');
        $sortAsc         = $request->boolean('asc', false);
        $companyId       = $request->get('company_id');

        $allowed = ['code', 'label', 'created_at', 'statu', 'companies_id', 'delivery_lines_count'];
        if (!in_array($sortField, $allowed)) {
            $sortField = 'created_at';
        }

        $dir = $sortAsc ? 'asc' : 'desc';

        $query = Deliverys::withCount('DeliveryLines')
            ->with(['companie:id,label', 'UserManagement:id,name'])
            ->when($search, fn ($q) => $q->where('label', 'like', '%'.$search.'%'))
            ->when($statuses, fn ($q) => $q->whereIn('statu', $statuses))
            ->when($invoiceStatuses, fn ($q) => $q->whereIn('invoice_status', $invoiceStatuses))
            ->when($companyId, fn ($q) => $q->where('companies_id', $companyId));

        match ($sortField) {
            'delivery_lines_count' => $query->orderBy('delivery_lines_count', $dir),
            'companies_id'         => $query->orderByRaw("(SELECT label FROM companies WHERE companies.id = deliverys.companies_id) {$dir}"),
            default                => $query->orderBy($sortField, $dir),
        };

        $deliverys = $query->paginate(15);

        return response()->json([
            'data' => $deliverys->map(fn ($d) => [
                'id'             => $d->id,
                'code'           => $d->code,
                'label'          => $d->label,
                'statu'          => $d->statu,
                'invoice_status' => $d->invoice_status,
                'lines_count'    => $d->delivery_lines_count,
                'companie'       => $d->companie ? ['id' => $d->companie->id, 'label' => $d->companie->label] : null,
                'user'           => $d->UserManagement ? ['name' => $d->UserManagement->name] : null,
                'created_at'     => $d->created_at?->format('d/m/Y'),
                'url'            => route('deliverys.show', ['id' => $d->id]),
                'pdf_url'        => route('pdf.delivery', ['Document' => $d->id]),
            ]),
            'meta' => [
                'total'        => $deliverys->total(),
                'per_page'     => $deliverys->perPage(),
                'current_page' => $deliverys->currentPage(),
                'last_page'    => $deliverys->lastPage(),
            ],
        ]);
    }

    public function packagingsUpdate(UpdatePackagingRequest $request, Deliverys $id)
    {
        $packaging = Packaging::findOrFail($request->id);
        $packaging->update([
            'code' => $request->code,
            'type' => $request->type,
            'status' => $request->status,
            'gross_weight' => $request->gross_weight,
            'net_weight' => $request->net_weight,
            'length' => $request->length,
            'width' => $request->width,
            'height' => $request->height,
            'comment' => $request->comment,
            'packing_date' => $request->packing_date,
            'loaded_date' => $request->loaded_date,
        ]);

        return redirect()->route('deliverys.show', ['id' =>  $packaging->deliverys_id])->with('success', 'Successfully update packaging');
    }

    public function companyAc(Request $request)
    {
        $companyId = (int) $request->get('company_id');
        $addresses = $this->SelectDataService->getAddress($companyId);
        $contacts  = $this->SelectDataService->getContact($companyId);

        return response()->json([
            'addresses' => $addresses->map(fn($a) => [
                'id'    => $a->id,
                'label' => trim($a->label . ($a->adress ? ' — ' . $a->adress : '')),
            ])->values(),
            'contacts' => $contacts->map(fn($c) => [
                'id'    => $c->id,
                'label' => trim($c->first_name . ' ' . $c->name),
            ])->values(),
        ]);
    }

    public function linesJson(Deliverys $id)
    {
        $lines = DeliveryLines::with('OrderLine:id,code,label')
            ->where('deliverys_id', $id->id)
            ->orderBy('ordre')
            ->get();

        return response()->json([
            'data' => $lines->map(fn ($l) => [
                'id'    => $l->id,
                'ordre' => $l->ordre,
                'label' => $l->OrderLine?->label ?? $l->OrderLine?->code ?? '',
            ]),
        ]);
    }

}
