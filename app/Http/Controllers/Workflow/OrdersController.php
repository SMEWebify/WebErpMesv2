<?php

namespace App\Http\Controllers\Workflow;

use Illuminate\Support\Number;
use App\Models\Workflow\Orders;
use App\Services\OrderKPIService;
use App\Traits\NextPreviousTrait;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use App\Services\CustomFieldService;
use App\Services\OrderCalculatorService;
use App\Services\OrderInvoiceDataService;
use App\Services\OrderBusinessBalanceService;
use App\Http\Requests\Workflow\UpdateOrderRequest;

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

        
        $remainingDeliveryOrder = Number::currency($remainingDeliveryOrder->orderSum , $factory->curency, config('app.locale'));
        $remainingInvoiceOrder = Number::currency($remainingInvoiceOrder->orderSum , $factory->curency, config('app.locale'));

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

        $id->load(['OrderSite.OrderSiteImplantations']);

        // Retrieve necessary data for dropdowns
        $CompanieSelect = $this->SelectDataService->getCompanies();
        $AddressSelect = $this->SelectDataService->getAddress($id->companies_id);
        $ContactSelect = $this->SelectDataService->getContact($id->companies_id);
        $AccountingConditionSelect = $this->SelectDataService->getAccountingPaymentConditions();
        $AccountingMethodsSelect = $this->SelectDataService->getAccountingPaymentMethod();
        $AccountingDeleveriesSelect = $this->SelectDataService->getAccountingDelivery();

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
        $stillInvoiced = Number::currency($totalPrice - $invoicedAmount, $factory->curency, config('app.locale'));
        $totalPrice = Number::currency($totalPrice, $factory->curency, config('app.locale'));
        $subPrice = Number::currency($subPrice, $factory->curency, config('app.locale'));
        $invoicedAmount = Number::currency($invoicedAmount, $factory->curency, config('app.locale'));
        $forecastMarginFormatted = Number::currency($forecastMargin, $factory->curency, config('app.locale'));
        $currentMarginFormatted = Number::currency($currentMargin, $factory->curency, config('app.locale'));
        $forecastMarginPercentageFormatted = number_format($forecastMarginPercentage, 2, '.', ',') . ' %';
        $currentMarginPercentageFormatted = number_format($currentMarginPercentage, 2, '.', ',') . ' %';

        $leadTime = $this->orderKPIService->getLeadTime($id);

        return view('workflow/orders-show', data: [
            'Order' => $id,
            'CompanieSelect' => $CompanieSelect,
            'AddressSelect' => $AddressSelect,
            'ContactSelect' => $ContactSelect,
            'AccountingConditionSelect' => $AccountingConditionSelect,
            'AccountingMethodsSelect' => $AccountingMethodsSelect,
            'AccountingDeleveriesSelect' => $AccountingDeleveriesSelect,
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

        // Redirect with success message
        return redirect()->route('orders.show', ['id' => $order->id])->with('success', 'Successfully updated Order');
    }
}

