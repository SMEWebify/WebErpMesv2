<?php

namespace App\Http\Controllers\Workflow;

use Carbon\Carbon;
use App\Models\Workflow\Orders;
use App\Models\Admin\CustomField;
use App\Services\OrderCalculatorService;
use App\Traits\NextPreviousTrait;
use Illuminate\Support\Facades\DB;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workflow\UpdateOrderRequest;

class OrdersController extends Controller
{
    use NextPreviousTrait;
    protected $SelectDataService;

    public function __construct(SelectDataService $SelectDataService)
    {
        $this->SelectDataService = $SelectDataService;
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {   
        $currentYear = Carbon::now()->format('Y');
        //Order data for chart
        $ordersDataRate = DB::table('orders')
                                    ->select('statu', DB::raw('count(*) as OrderCountRate'))
                                    ->groupBy('statu')
                                    ->get();
        //Order data for chart
        $orderMonthlyRecap = DB::table('order_lines')->selectRaw('
                                                                MONTH(delivery_date) AS month,
                                                                SUM((selling_price * qty)-(selling_price * qty)*(discount/100)) AS orderSum
                                                            ')
                                                            ->whereYear('created_at', $currentYear)
                                                            ->groupByRaw('MONTH(delivery_date) ')
                                                            ->get();

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
        $AddressSelect = $this->SelectDataService->getAddress();
        $ContactSelect = $this->SelectDataService->getContact();
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

        // Retrieve previous and next URLs for navigation
        list($previousUrl, $nextUrl) = $this->getNextPrevious(new Orders(), $id->id);

        // Fetch custom fields related to the order
        $CustomFields = CustomField::where('custom_fields.related_type', '=', 'order')
                                    ->leftJoin('custom_field_values  as cfv', function($join) use ($id) {
                                        $join->on('custom_fields.id', '=', 'cfv.custom_field_id')
                                                ->where('cfv.entity_type', '=', 'order')
                                                ->where('cfv.entity_id', '=', $id->id);
                                    })
                                    ->select('custom_fields.*', 'cfv.value as field_value')
                                    ->get();

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

