<?php

namespace App\Http\Controllers\Workflow;

use Illuminate\Support\Number;
use App\Models\Workflow\Orders;
use App\Jobs\CalculateTaskDates;
use App\Services\OrderKPIService;
use App\Traits\NextPreviousTrait;
use App\Models\Admin\Factory;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use App\Services\CustomFieldService;
use App\Services\OrderCalculatorService;
use App\Services\OrderInvoiceDataService;
use App\Services\OrderBusinessBalanceService;
use App\Models\Workflow\OrderLines;
use App\Http\Requests\Workflow\UpdateOrderRequest;
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

    public function __construct(
                                SelectDataService $SelectDataService, 
                                OrderKPIService $orderKPIService,
                                CustomFieldService $customFieldService,
                                OrderBusinessBalanceService $OrderBusinessBalanceService, 
                                OrderInvoiceDataService $OrderInvoiceDataService,
                    ){
        $this->SelectDataService = $SelectDataService;
        $this->orderKPIService = $orderKPIService;
        $this->customFieldService = $customFieldService;
        $this->OrderBusinessBalanceService = $OrderBusinessBalanceService;
        $this->OrderInvoiceDataService = $OrderInvoiceDataService;
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    { 
        $factory = app('Factory');   
        $CurentYear = now()->year;
        $currency = $factory->curency ?? 'EUR';

        // Récupérer les KPI
        $deliveredOrdersPercentage = $this->orderKPIService->getDeliveredOrdersPercentage();
        $invoicedOrdersPercentage = $this->orderKPIService->getInvoicedOrdersPercentage();
        $pendingDeliveries = $this->orderKPIService->getPendingDeliveries();
        $lateOrdersCount = $this->orderKPIService->getLateOrdersCount();
        $remainingDeliveryOrder =   $this->orderKPIService->getOrderMonthlyRemainingToDelivery(now()->month, $CurentYear);
        $remainingInvoiceOrder =   $this->orderKPIService->getOrderMonthlyRemainingToInvoice();
        $serviceRate =   $this->orderKPIService->getServiceRate();
        $topCustomers = $this->orderKPIService->getTopCustomersByOrderVolume(3);
        $averageProcessingTime = $this->orderKPIService->getAverageOrderProcessingTime();
        $data['ordersDataRate']= $this->orderKPIService->getOrdersDataRate();
        $data['orderMonthlyRecap'] = $this->orderKPIService->getOrderMonthlyRecap($CurentYear);
        $data['orderMonthlyRecapPreviousYear'] = $this->orderKPIService->getOrderMonthlyRecapPreviousYear($CurentYear);

        
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
        $businessBalancetotals = $this->OrderBusinessBalanceService->getBusinessBalanceTotals($id);
        $invoicedAmount = $this->OrderInvoiceDataService->getInvoicingAmount($id);
        $receivedPayment = $this->OrderInvoiceDataService->getInvoicingReceivedPayment($id);

        list($previousUrl, $nextUrl) = $this->getNextPrevious(new Orders(), $id->id);
        $CustomFields = $this->customFieldService->getCustomFieldsWithValues('order', $id->id);

        $percentageInvoiced = 100;
        if ($invoicedAmount > 0) {
            $percentageInvoiced = number_format($totalPrice / $invoicedAmount * 100, 2, '.', ',');
        }

        $forecastMargin = $totalPrice - $businessBalancetotals['total_cost'];
        $currentMargin = $totalPrice - $businessBalancetotals['realized_cost'];

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

        // Update the order using mass assignment
        $order->update($request->validated());

        if ($request->boolean('apply_delivery_date') && $order->validity_date) {
            $factory = Factory::first();
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
}
