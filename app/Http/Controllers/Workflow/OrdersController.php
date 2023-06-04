<?php

namespace App\Http\Controllers\Workflow;

use Carbon\Carbon;
use App\Models\Admin\Factory;
use App\Models\Planning\Status;
use App\Models\Workflow\Orders;
use App\Services\OrderCalculator;
use Illuminate\Support\Facades\DB;
use App\Models\Companies\Companies;
use App\Http\Controllers\Controller;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Accounting\AccountingDelivery;
use App\Http\Requests\Workflow\UpdateOrderRequest;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingPaymentConditions;

class OrdersController extends Controller
{
    /**
     * @return View
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
     * @return View
     */
    public function show(Orders $id)
    {
        $CompanieSelect = Companies::select('id', 'code','label')->where('active', 1)->get();
        $AddressSelect = CompaniesAddresses::select('id', 'label','adress')->get();
        $ContactSelect = CompaniesContacts::select('id', 'first_name','name')->get();
        $AccountingConditionSelect = AccountingPaymentConditions::select('id', 'code','label')->get();
        $AccountingMethodsSelect = AccountingPaymentMethod::select('id', 'code','label')->get();
        $AccountingDeleveriesSelect = AccountingDelivery::select('id', 'code','label')->get();
        $OrderCalculator = new OrderCalculator($id);
        $totalPrice = $OrderCalculator->getTotalPrice();
        $subPrice = $OrderCalculator->getSubTotal();
        $vatPrice = $OrderCalculator->getVatTotal();
        $previousUrl = route('orders.show', ['id' => $id->id-1]);
        $nextUrl = route('orders.show', ['id' => $id->id+1]);

        //DB information mustn't be empty.
        $Factory = Factory::first();
        if(!$Factory){
            return redirect()->route('admin.factory')->with('error', 'Please check factory information');
        }
        $Status = Status::select('id')->orderBy('order')->first();
        if(!$Status){
            return redirect()->route('admin.factory')->withErrors('Please add Kanban information before');
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
            'previousUrl' =>  $previousUrl,
            'nextUrl' =>  $nextUrl,
        ]);
    }

    
    /**
     * @param Request $request
     * @return View
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

