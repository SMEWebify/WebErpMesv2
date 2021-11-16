<?php

namespace App\Http\Controllers\workflow;

use App\Models\User;
use App\Models\Admin\Factory;
use App\Models\Workflow\Orders;
use App\ServiceS\OrderCalculator;
use Illuminate\Support\Facades\DB;
use App\Models\Companies\Companies;
use App\Models\Workflow\OrderLines;
use App\Http\Controllers\Controller;
use App\Models\Companies\companiesContacts;
use App\Models\Companies\companiesAddresses;
use App\Models\Accounting\AccountingDelivery;

use App\Http\Requests\Workflow\StoreOrderRequest;
use App\Http\Requests\Workflow\UpdateOrderRequest;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingPaymentConditions;

class OrdersController extends Controller
{
    //

    public function index()
    {    
        $Orders = Orders::All();
        $OrderLines = OrderLines::All();
        $userSelect = User::select('id', 'name')->get();
        $CompanieSelect = Companies::select('id', 'CODE','LABEL')->get();
        $AddressSelect = companiesAddresses::select('id', 'LABEL','ADRESS')->get();
        $ContactSelect = companiesContacts::select('id', 'FIRST_NAME','NAME')->get();
        $LastOrder =  DB::table('Orders')->orderBy('id', 'desc')->first();

        $AccountingConditionSelect = AccountingPaymentConditions::select('id', 'CODE','LABEL')->get();
        $AccountingMethodsSelect = AccountingPaymentMethod::select('id', 'CODE','LABEL')->get();
        $AccountingDeleveriesSelect = AccountingDelivery::select('id', 'CODE','LABEL')->get();

        return view('workflow/orders-index', [
            'Orderslist' => $Orders,
            'OrderLineslist' => $OrderLines,
            'LastOrder' => $LastOrder,
            'CompanieSelect' => $CompanieSelect,
            'AddressSelect' => $AddressSelect,
            'ContactSelect' => $ContactSelect,
            'userSelect' => $userSelect,
            'AccountingConditionSelect' => $AccountingConditionSelect,
            'AccountingMethodsSelect' => $AccountingMethodsSelect,
            'AccountingDeleveriesSelect' => $AccountingDeleveriesSelect,
        ]);
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
        
        $Factory = Factory::first();
        if(!$Factory){
            return redirect()->route('admin.factory')->with('danger', 'Please check factory information');
        }
        
        $AccountingConditionSelect = AccountingPaymentConditions::select('id', 'CODE','LABEL')->get();
        $AccountingMethodsSelect = AccountingPaymentMethod::select('id', 'CODE','LABEL')->get();
        $AccountingDeleveriesSelect = AccountingDelivery::select('id', 'CODE','LABEL')->get();
        
        $OrderCalculator = new OrderCalculator($id);
        $totalPrice = $OrderCalculator->getTotalPrice();
        $subPrice = $OrderCalculator->getSubTotal();
        $vatPrice = $OrderCalculator->getVatTotal();

        return view('workflow/Orders-show', [
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
        return view('workflow/Orders-print', [
            'Order' => $id,
            'Factory' => $Factory,
            'totalPrices' => $totalPrice,
            'subPrice' => $subPrice, 
            'vatPrice' => $vatPrice,
        ]);
    }

    public function store(StoreOrderRequest $request)
    {
       
        $Order = Orders::create($request->only('CODE', 
                                                        'LABEL', 
                                                        'customer_reference',
                                                        'companies_id', 
                                                        'companies_contacts_id',   
                                                        'companies_addresses_id',  
                                                        'validity_date',  
                                                        'statu',  
                                                        'user_id',  
                                                        'accounting_payment_conditions_id',  
                                                        'accounting_payment_methods_id',  
                                                        'accounting_deliveries_id',  
                                                        'comment'
                                                ));

        return redirect()->route('Order.show', ['id' => $Order->id])->with('success', 'Successfully created new Order');

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

        $OrderLines = OrderLines::where('orders_id', $request->id)->update(['statu' => $request->statu]);

        return redirect()->route('order.show', ['id' =>  $Order->id])->with('success', 'Successfully updated Order');

    }
}

