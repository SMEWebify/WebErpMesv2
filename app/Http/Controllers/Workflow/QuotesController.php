<?php

namespace App\Http\Controllers\Workflow;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Workflow\Quotes;
use Illuminate\Support\Facades\DB;
use App\Models\Companies\Companies;
use App\Models\Workflow\QuoteLines;
use App\Http\Controllers\Controller;
use App\Models\Companies\companiesContacts;
use App\Models\Companies\companiesAddresses;
use App\Models\Accounting\AccountingDelivery;
use App\Http\Requests\Workflow\StoreQuoteRequest;
use App\Http\Requests\Workflow\UpdateQuoteRequest;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingPaymentConditions;
use App\Models\Admin\Factory;

use App\ServiceS\QuoteCalculator;

class QuotesController extends Controller
{
    //

    public function index()
    {    
        $Quotes = Quotes::All();
        $QuoteLines = QuoteLines::All();
        $userSelect = User::select('id', 'name')->get();
        $CompanieSelect = Companies::select('id', 'CODE','LABEL')->get();
        $AddressSelect = companiesAddresses::select('id', 'LABEL','ADRESS')->get();
        $ContactSelect = companiesContacts::select('id', 'FIRST_NAME','NAME')->get();
        $LastQuote =  DB::table('quotes')->orderBy('id', 'desc')->first();

        $AccountingConditionSelect = AccountingPaymentConditions::select('id', 'CODE','LABEL')->get();
        $AccountingMethodsSelect = AccountingPaymentMethod::select('id', 'CODE','LABEL')->get();
        $AccountingDeleveriesSelect = AccountingDelivery::select('id', 'CODE','LABEL')->get();

        return view('workflow/quotes-index', [
            'Quoteslist' => $Quotes,
            'QuoteLineslist' => $QuoteLines,
            'LastQuote' => $LastQuote,
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
     * @param Quotes $Quotes
     * @return \Illuminate\Http\Response
     */

    public function show(Quotes $id)
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
        
        $QuoteCalculator = new QuoteCalculator($id);
        $totalPrice = $QuoteCalculator->getTotalPrice();
        $subPrice = $QuoteCalculator->getSubTotal();
       // $vatPrice = $QuoteCalculator->getVatTotal();

        return view('workflow/quotes-show', [
            'Quote' => $id,
            'CompanieSelect' => $CompanieSelect,
            'AddressSelect' => $AddressSelect,
            'ContactSelect' => $ContactSelect,
            'AccountingConditionSelect' => $AccountingConditionSelect,
            'AccountingMethodsSelect' => $AccountingMethodsSelect,
            'AccountingDeleveriesSelect' => $AccountingDeleveriesSelect,
            'Factory' => $Factory,
            'totalPrices' => $totalPrice,
            'subPrice' => $subPrice, 
            //'vatPrice' => $vatPrice,
        ]);
    }

    public function print($id)
    {
        $Quote = Quotes::findOrFail($id);
        return view('workflow/quotes-print', [
            'Quote' => $Quote,
        ]);
    }

    public function store(StoreQuoteRequest $request)
    {
       
        $Quote = Quotes::create($request->only('CODE', 
                                                        'LABEL', 
                                                        'customer_reference',
                                                        'companies_id', 
                                                        'companies_contacts_id',   
                                                        'companies_addresses_id',  
                                                        'validity_date',  
                                                        'STATU',  
                                                        'user_id',  
                                                        'accounting_payment_conditions_id',  
                                                        'accounting_payment_methods_id',  
                                                        'accounting_deliveries_id',  
                                                        'comment'
                                                ));

        return redirect()->route('quote.show', ['id' => $Quote->id])->with('success', 'Successfully created new quote');

    }

    public function update(UpdateQuoteRequest $request)
    {
       
        $Quote = Quotes::find($request->id);
        $Quote->LABEL=$request->LABEL;
        $Quote->STATU=$request->STATU;
        $Quote->customer_reference=$request->customer_reference;
        $Quote->companies_id=$request->companies_id;
        $Quote->companies_contacts_id=$request->companies_contacts_id;
        $Quote->companies_addresses_id=$request->companies_addresses_id;
        $Quote->validity_date=$request->validity_date;
        $Quote->accounting_payment_conditions_id=$request->accounting_payment_conditions_id;
        $Quote->accounting_payment_methods_id=$request->accounting_payment_methods_id;
        $Quote->accounting_deliveries_id=$request->accounting_deliveries_id;
        $Quote->comment=$request->comment;
        $Quote->save();

        return redirect()->route('quote.show', ['id' =>  $Quote->id])->with('success', 'Successfully updated quote');

    }
}
