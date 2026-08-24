<?php

namespace App\Http\Controllers\Workflow;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Number;
use App\Models\User;
use App\Models\Workflow\Orders;
use App\Models\Planning\Task;
use App\Models\Planning\Status;
use App\Jobs\CalculateTaskDates;
use App\Services\OrderKPIService;
use App\Services\OrderService;
use App\Services\OrderConfirmationService;
use App\Services\DocumentCodeGenerator;
use App\Traits\NextPreviousTrait;
use App\Models\Admin\Factory;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use App\Services\CustomFieldService;
use App\Services\OrderCalculatorService;
use App\Services\OrderInvoiceDataService;
use App\Services\OrderBusinessBalanceService;
use App\Models\Workflow\OrderLines;
use App\Models\Purchases\PurchaseLines;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompanyDocumentDefault;
use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingPaymentConditions;
use App\Http\Requests\Workflow\UpdateOrderRequest;
use App\Events\OrderStatusChanged;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\Models\Activity;

class OrdersController extends Controller
{
    use NextPreviousTrait;

    protected $SelectDataService;
    protected $orderKPIService;
    protected $customFieldService;
    protected $OrderBusinessBalanceService;
    protected $OrderInvoiceDataService;
    protected $orderConfirmationService;

    public function __construct(
                                SelectDataService $SelectDataService,
                                OrderKPIService $orderKPIService,
                                CustomFieldService $customFieldService,
                                OrderBusinessBalanceService $OrderBusinessBalanceService,
                                OrderInvoiceDataService $OrderInvoiceDataService,
                                OrderConfirmationService $orderConfirmationService,
                    ){
        $this->SelectDataService = $SelectDataService;
        $this->orderKPIService = $orderKPIService;
        $this->customFieldService = $customFieldService;
        $this->OrderBusinessBalanceService = $OrderBusinessBalanceService;
        $this->OrderInvoiceDataService = $OrderInvoiceDataService;
        $this->orderConfirmationService = $orderConfirmationService;
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    { 
        $factory = app('Factory');
        $CurentYear = now()->year;
        $currency = $factory->curency ?? 'EUR';
        $fiscal          = $factory->getCurrentFiscalYear();
        $fiscalStart     = $fiscal['start'];
        $fiscalEnd       = $fiscal['end'];
        $prevFiscalStart = $fiscalStart->copy()->subYear();
        $prevFiscalEnd   = $fiscalEnd->copy()->subYear();

        // Récupérer les KPI
        $deliveredOrdersPercentage = $this->orderKPIService->getDeliveredOrdersPercentage();
        $invoicedOrdersPercentage = $this->orderKPIService->getInvoicedOrdersPercentage();
        $pendingDeliveries = $this->orderKPIService->getPendingDeliveries();
        $lateOrdersCount = $this->orderKPIService->getLateOrdersCount();
        $remainingDeliveryOrder = $this->orderKPIService->getOrderMonthlyRemainingToDelivery(now()->month, $CurentYear);
        $remainingInvoiceOrder  = $this->orderKPIService->getOrderMonthlyRemainingToInvoice();
        $serviceRate = $this->orderKPIService->getServiceRate();
        $topCustomers = $this->orderKPIService->getTopCustomersByOrderVolume(3);
        $averageProcessingTime = $this->orderKPIService->getAverageOrderProcessingTime();
        $data['ordersDataRate'] = $this->orderKPIService->getOrdersDataRate();
        $data['orderMonthlyRecap'] = $this->orderKPIService->getOrderMonthlyRecap($CurentYear, null, $fiscalStart, $fiscalEnd);
        $data['orderMonthlyRecapPreviousYear'] = $this->orderKPIService->getOrderMonthlyRecapPreviousYear($CurentYear, $prevFiscalStart, $prevFiscalEnd);

        
        $remainingDeliveryOrder = Number::currency($remainingDeliveryOrder->orderSum ?? 0, $currency, config('app.locale'));
        $remainingInvoiceOrder = Number::currency($remainingInvoiceOrder->orderSum ?? 0, $currency, config('app.locale'));

        return view('workflow/orders-index', compact(
            'deliveredOrdersPercentage',
            'invoicedOrdersPercentage',
            'pendingDeliveries',
            'lateOrdersCount',
            'remainingDeliveryOrder',
            'remainingInvoiceOrder',
            'serviceRate',
            'topCustomers',
            'averageProcessingTime',
            'data',
        ));
    }
    
    /**
     * @param $id
     * @return \Illuminate\Contracts\View\View
     */
    public function show(Orders $id)
    {
        $factory = app('Factory');

        $id->load(['OrderSite.OrderSiteImplantations', 'OrderLines.OrderLineDetails']);

        // Retrieve necessary data for dropdowns
        $CompanieSelect = $this->SelectDataService->getCompanies();
        $AddressSelect = $this->SelectDataService->getAddress($id->companies_id);
        $ContactSelect = $this->SelectDataService->getContact($id->companies_id);
        $AccountingConditionSelect = $this->SelectDataService->getAccountingPaymentConditions();
        $AccountingMethodsSelect = $this->SelectDataService->getAccountingPaymentMethod();
        $AccountingDeleveriesSelect = $this->SelectDataService->getAccountingDelivery();
        $MethodsLocationsSelect = $this->SelectDataService->getMethodsLocations();
        $Reviewers = $this->SelectDataService->getUsers();

        // Initialize OrderCalculatorService with the order ID
        $OrderCalculatorService = new OrderCalculatorService($id);

        // Calculate various prices and times
        $totalPrice = $OrderCalculatorService->getTotalPrice();
        $subPrice = $OrderCalculatorService->getSubTotal();
        $vatPrice = $OrderCalculatorService->getVatTotal();
        $TotalServiceProductTime = $OrderCalculatorService->getTotalProductTimeByService();
        $TotalServiceSettingTime = $OrderCalculatorService->getTotalSettingTimeByService();
        $TotalServiceCost = $OrderCalculatorService->getTotalCostByService();
        $TotalServicePrice = $OrderCalculatorService->getTotalPriceByService();
        
        $businessBalance = $this->OrderBusinessBalanceService->getBusinessBalance($id);
        $businessBalancetotals = $this->OrderBusinessBalanceService->getBusinessBalanceTotals($businessBalance);
        $stockConsumptions = $this->OrderBusinessBalanceService->getStockConsumptionDetails($id);
        $invoicedAmount = $this->OrderInvoiceDataService->getInvoicingAmount($id);
        $receivedPayment = $this->OrderInvoiceDataService->getInvoicingReceivedPayment($id);

        list($previousUrl, $nextUrl) = $this->getNextPrevious(new Orders(), $id->id);
        $CustomFields = $this->customFieldService->getCustomFieldsWithValues('order', $id->id);

        $percentageInvoiced = 100;
        if ($invoicedAmount > 0) {
            $percentageInvoiced = number_format($totalPrice / $invoicedAmount * 100, 2, '.', ',');
        }

        $forecastMargin = $totalPrice - $businessBalancetotals['total_cost'];
        $currentMargin = $totalPrice - $businessBalancetotals['realized_cost'] - $businessBalancetotals['realized_purchase_cost'] - $businessBalancetotals['realized_stock_cost'];

        // Calcul des marges en pourcentage (avec gestion des divisions par zéro)
        $forecastMarginPercentage = $businessBalancetotals['total_cost'] > 0
            ? ($forecastMargin / $businessBalancetotals['total_cost']) * 100
            : 0;

        $currentMarginPercentage = $businessBalancetotals['realized_cost'] > 0
            ? ($currentMargin / $businessBalancetotals['realized_cost']) * 100
            : 0;


        //format variable after calculation for display
        $currency = $factory->curency ?? 'EUR';
        $stillInvoiced = Number::currency($totalPrice - $invoicedAmount, $currency, config('app.locale'));
        $totalPrice = Number::currency($totalPrice, $currency, config('app.locale'));
        $subPrice = Number::currency($subPrice, $currency, config('app.locale'));
        $invoicedAmount = Number::currency($invoicedAmount, $currency, config('app.locale'));
        $forecastMarginFormatted = Number::currency($forecastMargin, $currency, config('app.locale'));
        $currentMarginFormatted = Number::currency($currentMargin, $currency, config('app.locale'));
        $forecastMarginPercentageFormatted = number_format($forecastMarginPercentage, 2, '.', ',') . ' %';
        $currentMarginPercentageFormatted = number_format($currentMarginPercentage, 2, '.', ',') . ' %';

        $leadTime = $this->orderKPIService->getLeadTime($id);
        $reviewFields = ['reviewed_by', 'reviewed_at', 'review_decision', 'change_requested_by', 'change_reason', 'change_approved_at'];
        $ReviewTimeline = Activity::query()
            ->where('subject_type', Orders::class)
            ->where('subject_id', $id->id)
            ->with('causer')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Activity $activity) use ($reviewFields) {
                $properties = $activity->properties?->toArray() ?? [];
                $attributes = array_intersect_key(data_get($properties, 'attributes', []), array_flip($reviewFields));
                $old = array_intersect_key(data_get($properties, 'old', []), array_flip($reviewFields));

                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'causer' => $activity->causer?->name,
                    'created_at' => $activity->created_at,
                    'changes' => collect($attributes)->map(function ($newValue, $field) use ($old) {
                        return [
                            'field' => $field,
                            'old' => $old[$field] ?? null,
                            'new' => $newValue,
                        ];
                    })->values()->all(),
                ];
            })
            ->filter(fn ($entry) => !empty($entry['changes']))
            ->values();

        return view('workflow/orders-show', data: [
            'Order' => $id,
            'CompanieSelect' => $CompanieSelect,
            'AddressSelect' => $AddressSelect,
            'ContactSelect' => $ContactSelect,
            'AccountingConditionSelect' => $AccountingConditionSelect,
            'AccountingMethodsSelect' => $AccountingMethodsSelect,
            'AccountingDeleveriesSelect' => $AccountingDeleveriesSelect,
            'MethodsLocationsSelect' => $MethodsLocationsSelect,
            'Reviewers' => $Reviewers,
            'ReviewTimeline' => $ReviewTimeline,
            'totalPrices' => $totalPrice,
            'subPrice' => $subPrice, 
            'vatPrice' => $vatPrice,
            'TotalServiceProductTime'=> $TotalServiceProductTime,
            'TotalServiceSettingTime'=> $TotalServiceSettingTime,
            'TotalServiceCost'=> $TotalServiceCost,
            'TotalServicePrice'=> $TotalServicePrice,
            'previousUrl' =>  $previousUrl,
            'nextUrl' =>  $nextUrl,
            'CustomFields' => $CustomFields,
            'businessBalance' => $businessBalance,
            'businessBalancetotals' => $businessBalancetotals,
            'invoicedAmount' => $invoicedAmount,
            'receivedPayment' => $receivedPayment,
            'stillInvoiced' => $stillInvoiced,
            'percentageInvoiced' => $percentageInvoiced,
            'forecastMarginFormatted' => $forecastMarginFormatted,
            'currentMarginFormatted' => $currentMarginFormatted,
            'forecastMarginPercentageFormatted' => $forecastMarginPercentageFormatted,
            'currentMarginPercentageFormatted' => $currentMarginPercentageFormatted,
            'leadTime' => $leadTime,
            'stockConsumptions' => $stockConsumptions,
            'OrderSite' => $id->OrderSite,
            'OrderSiteImplantations' => $id->OrderSite ? $id->OrderSite->OrderSiteImplantations : collect(),
        ]);
    }
    
    /**
     * @param \App\Http\Requests\Workflow\UpdateOrderRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateOrderRequest $request)
    {
        // Retrieve the order
        $order = Orders::findOrFail($request->id);

        $previousDecision = $order->review_decision;

        // Update the order using mass assignment
        $order->update($request->validated());

        // La revue approuvée constitue l'ARC. Tant qu'il reste en cours, il est
        // resynchronisé plutôt que dupliqué : c'est une photo de la commande.
        if ($order->review_decision === 'approved' && $previousDecision !== 'approved' && $order->type == 1) {
            $this->orderConfirmationService->createFromOrder($order, auth()->id());
        }

        if ($request->boolean('apply_delivery_date') && $order->validity_date) {
            $factory = app('Factory');
            $updates = ['delivery_date' => $order->validity_date];

            if ($factory) {
                $date = date_create($order->validity_date);
                $internalDelay = date_format(
                    date_sub($date, date_interval_create_from_date_string($factory->add_delivery_delay_order . ' days')),
                    'Y-m-d'
                );
                $updates['internal_delay'] = $internalDelay;
            }

            OrderLines::where('orders_id', $order->id)->update($updates);
        }

        // Redirect with success message
        return redirect()->route('orders.show', ['id' => $order->id])->with('success', 'Successfully updated Order');
    }

    public function calculateTaskDates(Orders $order)
    {
        Cache::forget(CalculateTaskDates::cacheKeyForOrder($order->id));
        CalculateTaskDates::dispatchAfterResponse($order->id);

        return redirect()
            ->route('orders.show', ['id' => $order->id])
            ->with('success', 'Task date calculation queued for this order.');
    }

    // -------------------------------------------------------------------------
    // JSON endpoints for the React OrdersIndex component
    // -------------------------------------------------------------------------

    public function listJson(Request $request)
    {
        $search    = $request->get('search', '');
        $statuses  = array_filter(array_map('intval', (array) $request->get('statuses', [1, 2])));
        $sortField = $request->get('sort', 'created_at');
        $sortAsc   = $request->boolean('asc', false);
        $companyId = $request->get('company_id');

        $allowed = ['code', 'label', 'created_at', 'validity_date', 'statu', 'companie', 'contact', 'order_lines_count', 'total_amount'];
        if (!in_array($sortField, $allowed)) {
            $sortField = 'created_at';
        }

        $dir      = $sortAsc ? 'asc' : 'desc';
        $totalSub = 'COALESCE((SELECT SUM(selling_price * qty * (1 - COALESCE(discount,0)/100)) FROM order_lines WHERE order_lines.orders_id = orders.id AND order_lines.deleted_at IS NULL), 0)';

        $query = Orders::withCount('OrderLines')
            ->selectRaw("orders.*, {$totalSub} as total_amount")
            ->with(['companie:id,label,code', 'contact:id,first_name,name'])
            ->when($search, fn ($q) => $q->where('label', 'like', '%'.$search.'%'))
            ->when($statuses, fn ($q) => $q->whereIn('statu', $statuses))
            ->when($companyId, fn ($q) => $q->where('companies_id', $companyId));

        match ($sortField) {
            'companie'          => $query->orderByRaw("(SELECT label FROM companies WHERE companies.id = orders.companies_id) {$dir}"),
            'contact'           => $query->orderByRaw("(SELECT name FROM companies_contacts WHERE companies_contacts.id = orders.companies_contacts_id) {$dir}"),
            'order_lines_count' => $query->orderBy('order_lines_count', $dir),
            'total_amount'      => $query->orderByRaw("{$totalSub} {$dir}"),
            default             => $query->orderBy($sortField, $dir),
        };

        $orders = $query->paginate(15);

        return response()->json([
            'data' => $orders->map(fn ($o) => [
                'id'                 => $o->id,
                'code'               => $o->code,
                'label'              => $o->label,
                'customer_reference' => $o->customer_reference,
                'statu'              => $o->statu,
                'validity_date'      => $o->validity_date,
                'created_at'         => $o->created_at?->format('d/m/Y'),
                'companie'           => $o->companie ? ['id' => $o->companie->id, 'label' => $o->companie->label] : null,
                'contact'            => $o->contact  ? ['id' => $o->contact->id,  'name'  => trim($o->contact->first_name.' '.$o->contact->name)] : null,
                'order_lines_count'  => $o->order_lines_count,
                'total_amount'       => round((float) $o->total_amount, 2),
                'url'                => route('orders.show', ['id' => $o->id]),
            ]),
            'meta' => [
                'total'        => $orders->total(),
                'per_page'     => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
            ],
        ]);
    }

    public function storeJson(Request $request)
    {
        abort_unless(auth()->check(), 403);

        $type = (int) $request->input('type', 1);

        $rules = [
            'code'               => 'required|unique:orders',
            'label'              => 'required',
            'user_id'            => 'required|integer',
            'validity_date'      => 'nullable|date',
            'comment'            => 'nullable|string',
            'customer_reference' => 'nullable|string|max:255',
            'type'               => 'required|in:1,2',
        ];

        if ($type === 1) {
            $rules['companies_id']                     = 'required|integer';
            $rules['companies_contacts_id']            = 'required|integer';
            $rules['companies_addresses_id']           = 'required|integer';
            $rules['accounting_payment_conditions_id'] = 'required|integer';
            $rules['accounting_payment_methods_id']    = 'required|integer';
            $rules['accounting_deliveries_id']         = 'required|integer';
        }

        $validated = $request->validate($rules);

        $orderService = app(OrderService::class);
        $order = $orderService->createOrder(
            $validated['code'],
            $validated['label'],
            $validated['customer_reference'] ?? null,
            $type === 1 ? ($validated['companies_id'] ?? null) : null,
            $type === 1 ? ($validated['companies_contacts_id'] ?? null) : null,
            $type === 1 ? ($validated['companies_addresses_id'] ?? null) : null,
            $validated['validity_date'] ?? null,
            1,
            $validated['user_id'],
            $type === 1 ? ($validated['accounting_payment_conditions_id'] ?? null) : null,
            $type === 1 ? ($validated['accounting_payment_methods_id'] ?? null) : null,
            $type === 1 ? ($validated['accounting_deliveries_id'] ?? null) : null,
            $validated['comment'] ?? null,
            $type,
            null,
            null
        );

        return response()->json([
            'redirect' => route('orders.show', ['id' => $order->id]),
        ], 201);
    }

    public function selectDataJson()
    {
        $docGen = app(DocumentCodeGenerator::class);

        return response()->json([
            'next_code_external'  => $docGen->peekNextCode('order'),
            'next_code_internal'  => $docGen->peekNextCode('internal-order'),
            'companies'           => $this->SelectDataService->getCompanies()->map(fn ($c) => [
                'id'    => $c->id,
                'label' => $c->label ?? $c->last_name,
                'code'  => $c->code,
            ]),
            'payment_conditions'  => AccountingPaymentConditions::select('id', 'code', 'label', 'default')->get(),
            'payment_methods'     => AccountingPaymentMethod::select('id', 'code', 'label', 'default')->get(),
            'deliveries'          => AccountingDelivery::select('id', 'code', 'label', 'default')->get(),
            'users'               => User::select('id', 'name')->get(),
        ]);
    }

    public function addressesJson(int $companyId)
    {
        $addresses = CompaniesAddresses::select('id', 'label', 'adress', 'default')
            ->where('companies_id', $companyId)
            ->get();

        $docDefault = CompanyDocumentDefault::where('companies_id', $companyId)
            ->where('document_type', 'order')
            ->first();

        return response()->json([
            'addresses'          => $addresses,
            'default_address_id' => $docDefault?->companies_addresses_id,
            'default_contact_id' => $docDefault?->companies_contacts_id,
        ]);
    }

    public function contactsJson(int $companyId)
    {
        return response()->json(
            CompaniesContacts::select('id', 'first_name', 'name', 'default')
                ->where('companies_id', $companyId)
                ->get()
                ->map(fn ($c) => ['id' => $c->id, 'name' => trim($c->first_name.' '.$c->name), 'default' => $c->default])
        );
    }

    public function storeAddressJson(Request $request)
    {
        abort_unless(auth()->check(), 403);

        $validated = $request->validate([
            'companies_id' => 'required|integer|exists:companies,id',
            'ordre'        => 'required|numeric|gt:0',
            'label'        => 'required|string|max:255',
            'adress'       => 'required|string|max:255',
            'zipcode'      => 'required|string|max:20',
            'city'         => 'required|string|max:100',
            'country'      => 'required|string|max:100',
            'number'       => 'nullable|string|max:50',
            'mail'         => 'nullable|email|max:255',
        ]);

        $address = CompaniesAddresses::create($validated);

        return response()->json([
            'id'    => $address->id,
            'label' => $address->label,
            'adress'=> $address->adress,
        ], 201);
    }

    public function storeContactJson(Request $request)
    {
        abort_unless(auth()->check(), 403);

        $validated = $request->validate([
            'companies_id' => 'required|integer|exists:companies,id',
            'ordre'        => 'required|numeric|gt:0',
            'civility'     => 'nullable|string|max:20',
            'first_name'   => 'required|string|max:100',
            'name'         => 'required|string|max:100',
            'function'     => 'nullable|string|max:100',
            'number'       => 'nullable|string|max:50',
            'mobile'       => 'nullable|string|max:50',
            'mail'         => 'nullable|email|max:255',
        ]);

        $contact = CompaniesContacts::create($validated);

        return response()->json([
            'id'   => $contact->id,
            'name' => trim($contact->first_name.' '.$contact->name),
        ], 201);
    }

    /**
     * JSON endpoint — timeline of unique purchase documents linked to this order.
     * Traverses: Order → OrderLines → Tasks → PurchaseLines → Purchases.
     */
    public function purchaseHistoryJson(int $id)
    {
        $order = Orders::findOrFail($id);

        $this->authorize('purchases-menu');

        $orderLineIds = $order->OrderLines()->pluck('id');

        $purchases = PurchaseLines::whereHas('tasks', function ($q) use ($orderLineIds) {
                $q->whereIn('order_lines_id', $orderLineIds);
            })
            ->with(['purchase:id,code,statu,created_at'])
            ->get()
            ->groupBy('purchases_id')
            ->map(function ($lines) {
                $purchase = $lines->first()->purchase;
                $count    = $lines->count();
                return [
                    'type'  => 'purchase',
                    'id'    => $purchase?->id,
                    'code'  => $purchase?->code,
                    'label' => $count === 1
                        ? $lines->first()->label
                        : $count . ' ligne' . ($count > 1 ? 's' : ''),
                    'statu' => $purchase?->statu,
                    'date'  => $purchase?->created_at?->format('Y-m-d'),
                    'url'   => $purchase ? route('purchases.show', ['id' => $purchase->id]) : '#',
                ];
            })
            ->sortByDesc('date')
            ->values();

        return response()->json($purchases);
    }

    public function changeStatusJson(Request $request, $id)
    {
        try {
            $statu = (int) $request->input('statu');
            $order = Orders::findOrFail($id);

            // Règle métier : une commande dont au moins une ligne a été facturée
            // ne peut plus être annulée — il faut passer par un avoir.
            if ($statu === 6 && $order->OrderLines()->where('invoiced_qty', '>', 0)->exists()) {
                return response()->json([
                    'error' => __('general_content.order_invoiced_no_cancel_trans_key'),
                ], 422);
            }

            $order->statu = $statu;
            $order->save();
            event(new OrderStatusChanged($order, $statu));

            $tasks = Task::whereHas('OrderLines', fn($q) => $q->where('orders_id', $id))->get();
            $statusStarted    = Status::where('title', 'Started')->first();
            $statusInProgress = Status::where('title', 'In progress')->first();
            $statusSuspended  = Status::where('title', 'Suspended')->first();
            $statusFinished   = Status::where('title', 'Finished')->first();

            foreach ($tasks as $task) {
                if ($statu === 2) {
                    $s = $statusStarted ?? $statusInProgress;
                    if ($s) $task->update(['status_id' => $s->id]);
                } elseif ($statu === 5) {
                    $s = $statusSuspended ?? $statusFinished;
                    if ($s) $task->update(['status_id' => $s->id]);
                } elseif ($statu === 6 && $statusFinished) {
                    $task->update(['status_id' => $statusFinished->id]);
                }
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Update failed'], 500);
        }
    }
}
