<?php

namespace App\Http\Controllers\workflow;

use Carbon\Carbon;
use App\Models\Admin\Factory;
use App\Models\Workflow\Orders;
use App\ServiceS\OrderCalculator;
use Illuminate\Support\Facades\DB;
use App\Models\Companies\Companies;
use App\Http\Controllers\Controller;
use App\Models\Companies\companiesContacts;
use App\Models\Companies\companiesAddresses;
use App\Models\Accounting\AccountingDelivery;
use App\Http\Requests\Workflow\UpdateOrderRequest;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingPaymentConditions;

class OrdersController extends Controller
{
    //
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
     * Display the specified resource.
     *
     * @param Orders $Orders
     * @return \Illuminate\Http\Response
     */

    public function show(Orders $id)
    {
        
        $CompanieSelect = Companies::select('id', 'CODE','LABEL')->get();
        $AddressSelect = companiesAddresses::select('id', 'LABEL','ADRESS')->get();
        $ContactSelect = companiesContacts::select('id', 'FIRST_NAME','NAME')->get();
        $AccountingConditionSelect = AccountingPaymentConditions::select('id', 'CODE','LABEL')->get();
        $AccountingMethodsSelect = AccountingPaymentMethod::select('id', 'CODE','LABEL')->get();
        $AccountingDeleveriesSelect = AccountingDelivery::select('id', 'CODE','LABEL')->get();
        $OrderCalculator = new OrderCalculator($id);
        $totalPrice = $OrderCalculator->getTotalPrice();
        $subPrice = $OrderCalculator->getSubTotal();
        $vatPrice = $OrderCalculator->getVatTotal();
        $Factory = Factory::first();
        if(!$Factory){
            return redirect()->route('admin.factory')->with('error', 'Please check factory information');
        }

        return view('workflow/orders-show', [
            'Order' => $id,
            'CompanieSelect' => $CompanieSelect,
            'AddressSelect' => $AddressSelect,
            'ContactSelect' => $ContactSelect,
            'AccountingConditionSelect' => $AccountingConditionSelect,
            'AccountingMethodsSelect' => $AccountingMethodsSelect,
            'AccountingDeleveriesSelect' => $AccountingDeleveriesSelect,
            'Factory' => $Factory,
            'totalPrices' => $totalPrice,
            'subPrice' => $subPrice, 
            'vatPrice' => $vatPrice,
        ]);
    }

    public function print(Orders $id)
    {
        $OrderCalculator = new OrderCalculator($id);
        $Factory = Factory::first();
        $totalPrice = $OrderCalculator->getTotalPrice();
        $subPrice = $OrderCalculator->getSubTotal();
        $vatPrice = $OrderCalculator->getVatTotal();
        return view('workflow/orders-print', [
            'Order' => $id,
            'Factory' => $Factory,
            'totalPrices' => $totalPrice,
            'subPrice' => $subPrice, 
            'vatPrice' => $vatPrice,
        ]);
    }
    
    public function update(UpdateOrderRequest $request)
    {
        $Order = Orders::find($request->id);
        $Order->LABEL=$request->LABEL;
        $Order->statu=$request->statu;
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
        return redirect()->route('order.show', ['id' =>  $Order->id])->with('success', 'Successfully updated Order');
    }
}

