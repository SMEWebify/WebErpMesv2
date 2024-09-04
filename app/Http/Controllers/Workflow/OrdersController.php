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
use App\Http\Requests\Workflow\UpdateOrderRequest;

class OrdersController extends Controller
{
    use NextPreviousTrait;

    protected $SelectDataService;
    protected $orderKPIService;
    protected $customFieldService;

    public function __construct(
                                SelectDataService $SelectDataService, 
                                OrderKPIService $orderKPIService,
                                CustomFieldService $customFieldService
                    ){
        $this->SelectDataService = $SelectDataService;
        $this->orderKPIService = $orderKPIService;
        $this->customFieldService = $customFieldService;
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {   
        $currentYear = Carbon::now()->format('Y');
        $ordersDataRate =  $this->orderKPIService->getOrdersDataRate();
        $orderMonthlyRecap = $this->orderKPIService->getOrderMonthlyRecap($currentYear);

        // Prepare data array
        $data = [
            'ordersDataRate' => $ordersDataRate,
            'orderMonthlyRecap' => $orderMonthlyRecap,
        ];

        return view('workflow/orders-index')->with('data',$data);
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

