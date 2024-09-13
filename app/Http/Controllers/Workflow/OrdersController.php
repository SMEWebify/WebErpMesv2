<?php

namespace App\Http\Controllers\Workflow;

use Carbon\Carbon;
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
        $CurentYear = now()->year;

        // Récupérer les KPI
        $deliveredOrdersPercentage = $this->orderKPIService->getDeliveredOrdersPercentage();
        $invoicedOrdersPercentage = $this->orderKPIService->getInvoicedOrdersPercentage();
        $pendingDeliveries = $this->orderKPIService->getPendingDeliveries();
        $lateOrdersCount = $this->orderKPIService->getLateOrdersCount();
        $remainingDeliveryOrder =   $this->orderKPIService->getOrderMonthlyRemainingToDelivery(now()->month, $CurentYear);
        $remainingInvoiceOrder =   $this->orderKPIService->getOrderMonthlyRemainingToInvoice();
        $data['ordersDataRate']= $this->orderKPIService->getOrdersDataRate();
        $data['orderMonthlyRecap'] = $this->orderKPIService->getOrderMonthlyRecap($CurentYear);
        $data['orderMonthlyRecapPreviousYear'] = $this->orderKPIService->getOrderMonthlyRecapPreviousYear($CurentYear);
        
        return view('workflow/orders-index', compact(
            'deliveredOrdersPercentage',
            'invoicedOrdersPercentage',
            'pendingDeliveries',
            'lateOrdersCount',
            'remainingDeliveryOrder',
            'remainingInvoiceOrder',
            'data',
        ));
    }
    
    /**
     * @param $id
     * @return \Illuminate\Contracts\View\View
     */
    public function show(Orders $id)
    {
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

        return view('workflow/orders-show', [
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
        ]);
    }
    
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateOrderRequest $request)
    {
        // Retrieve the order
        $order = Orders::findOrFail($request->id);

        // Update the order using mass assignment
        $order->update([
            'label' => $request->label,
            'customer_reference' => $request->customer_reference,
            'companies_id' => $request->companies_id,
            'companies_contacts_id' => $request->companies_contacts_id,
            'companies_addresses_id' => $request->companies_addresses_id,
            'validity_date' => $request->validity_date,
            'accounting_payment_conditions_id' => $request->accounting_payment_conditions_id,
            'accounting_payment_methods_id' => $request->accounting_payment_methods_id,
            'accounting_deliveries_id' => $request->accounting_deliveries_id,
            'comment' => $request->comment,
        ]);

        // Redirect with success message
        return redirect()->route('orders.show', ['id' =>  $order->id])->with('success', 'Successfully updated Order');
    }
}

