<?php

namespace App\Http\Controllers\Workflow;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Planning\Status;
use App\Models\Workflow\Quotes;
use App\Models\Admin\CustomField;
use App\Services\QuoteCalculator;
use Illuminate\Support\Facades\DB;
use App\Models\Companies\Companies;

use App\Models\Workflow\QuoteLines;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Accounting\AccountingDelivery;
use App\Http\Requests\Workflow\UpdateQuoteRequest;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingPaymentConditions;

class QuotesController extends Controller
{
    protected $SelectDataService;

    public function __construct(SelectDataService $SelectDataService)
    {
        $this->SelectDataService = $SelectDataService;
    }

    /**
     * @return View
     */
    public function index()
    {
        $CurentYear = Carbon::now()->format('Y');

        //Quote data for chart
        $data['quotesDataRate'] = DB::table('quotes')
                                    ->select('statu', DB::raw('count(*) as QuoteCountRate'))
                                    ->groupBy('statu')
                                    ->get();
        //Quote data for chart
        $data['quoteMonthlyRecap'] = DB::table('quote_lines')->selectRaw('
                                                                MONTH(delivery_date) AS month,
                                                                SUM((selling_price * qty)-(selling_price * qty)*(discount/100)) AS quoteSum
                                                            ')
                                                            ->whereYear('created_at', $CurentYear)
                                                            ->groupByRaw('MONTH(delivery_date) ')
                                                            ->get();

        return view('workflow/quotes-index')->with('data',$data);
    }

    /**
     * @param $id
     * @return View
     */
    public function show(Quotes $id)
    {
        $CompanieSelect = $this->SelectDataService->getCompanies();
        $AddressSelect = $this->SelectDataService->getAddress();
        $ContactSelect = $this->SelectDataService->getContact();
        $AccountingConditionSelect = $this->SelectDataService->getAccountingPaymentConditions();
        $AccountingMethodsSelect = $this->SelectDataService->getAccountingPaymentMethod();
        $AccountingDeleveriesSelect =  $this->SelectDataService->getAccountingDelivery();
        $QuoteCalculator = new QuoteCalculator($id);
        $totalPrice = $QuoteCalculator->getTotalPrice();
        $subPrice = $QuoteCalculator->getSubTotal();
        $vatPrice = $QuoteCalculator->getVatTotal();
        $TotalServiceProductTime = $QuoteCalculator->getTotalProductTimeByService();
        $TotalServiceSettingTime = $QuoteCalculator->getTotalSettingTimeByService();
        $TotalServiceCost = $QuoteCalculator->getTotalCostByService();
        $TotalServicePrice = $QuoteCalculator->getTotalPriceByService();
        $previousUrl = route('quotes.show', ['id' => $id->id-1]);
        $nextUrl = route('quotes.show', ['id' => $id->id+1]);
        $CustomFields = CustomField::where('custom_fields.related_type', '=', 'quote')
                                    ->leftJoin('custom_field_values  as cfv', function($join) use ($id) {
                                        $join->on('custom_fields.id', '=', 'cfv.custom_field_id')
                                                ->where('cfv.entity_type', '=', 'quote')
                                                ->where('cfv.entity_id', '=', $id->id);
                                    })
                                    ->select('custom_fields.*', 'cfv.value as field_value')
                                    ->get();

        return view('workflow/quotes-show', [
            'Quote' => $id,
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
     * @param Request $request
     * @return View
     */
    public function update(UpdateQuoteRequest $request)
    {
        $Quote = Quotes::findOrFail($request->id);
        $Quote->label=$request->label;
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
        
        return redirect()->route('quotes.show', ['id' =>  $Quote->id])->with('success', 'Successfully updated quote');
    }
}
