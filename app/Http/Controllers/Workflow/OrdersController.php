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
        $CurentYear = Carbon::now()->format('Y');
        //Order data for chart
        $data['ordersDataRate'] = DB::table('orders')
                                    ->select('statu', DB::raw('count(*) as OrderCountRate'))
                                    ->groupBy('statu')
                                    ->get();
        //Order data for chart
        $data['orderMonthlyRecap'] = DB::table('order_lines')->selectRaw('
                                                                MONTH(delivery_date) AS month,
                                                                SUM((selling_price * qty)-(selling_price * qty)*(discount/100)) AS orderSum
                                                            ')
                                                            ->whereYear('created_at', $CurentYear)
                                                            ->groupByRaw('MONTH(delivery_date) ')
                                                            ->get();

        return view('workflow/orders-index')->with('data',$data);
    }

    
    /**
     * @param $id
     * @return \Illuminate\Contracts\View\View
     */
    public function show(Orders $id)
    {
        $CompanieSelect = $this->SelectDataService->getCompanies();
        $AddressSelect = $this->SelectDataService->getAddress();
        $ContactSelect = $this->SelectDataService->getContact();
        $AccountingConditionSelect = $this->SelectDataService->getAccountingPaymentConditions();
        $AccountingMethodsSelect = $this->SelectDataService->getAccountingPaymentMethod();
        $AccountingDeleveriesSelect = $this->SelectDataService->getAccountingDelivery();
        $OrderCalculatorService = new OrderCalculatorService($id);
        $totalPrice = $OrderCalculatorService->getTotalPrice();
        $subPrice = $OrderCalculatorService->getSubTotal();
        $vatPrice = $OrderCalculatorService->getVatTotal();
        $TotalServiceProductTime = $OrderCalculatorService->getTotalProductTimeByService();
        $TotalServiceSettingTime = $OrderCalculatorService->getTotalSettingTimeByService();
        $TotalServiceCost = $OrderCalculatorService->getTotalCostByService();
        $TotalServicePrice = $OrderCalculatorService->getTotalPriceByService();
        list($previousUrl, $nextUrl) = $this->getNextPrevious(new Orders(), $id->id);
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
        $Order = Orders::find($request->id);
        $Order->label=$request->label;
        $Order->customer_reference=$request->customer_reference;
        $Order->companies_id=$request->companies_id;
        $Order->companies_contacts_id=$request->companies_contacts_id;
        $Order->companies_addresses_id=$request->companies_addresses_id;
        $Order->validity_date=$request->validity_date;
        $Order->accounting_payment_conditions_id=$request->accounting_payment_conditions_id;
        $Order->accounting_payment_methods_id=$request->accounting_payment_methods_id;
        $Order->accounting_deliveries_id=$request->accounting_deliveries_id;
        $Order->comment=$request->comment;
        $Order->save();
        return redirect()->route('orders.show', ['id' =>  $Order->id])->with('success', 'Successfully updated Order');
    }
}

