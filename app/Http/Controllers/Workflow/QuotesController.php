<?php

namespace App\Http\Controllers\Workflow;

use Carbon\Carbon;
use App\Models\Workflow\Quotes;
use App\Services\QuoteKPIService;
use App\Traits\NextPreviousTrait;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use App\Services\CustomFieldService;
use App\Services\QuoteCalculatorService;
use App\Http\Requests\Workflow\UpdateQuoteRequest;

class QuotesController extends Controller
{
    use NextPreviousTrait;
    protected $SelectDataService;
    protected $quoteKPIService;
    protected $customFieldService;

    public function __construct(
        SelectDataService $SelectDataService, 
        QuoteKPIService $quoteKPIService,
        CustomFieldService $customFieldService
        ){
        $this->SelectDataService = $SelectDataService;
        $this->quoteKPIService = $quoteKPIService;
        $this->customFieldService = $customFieldService;
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $CurentYear = Carbon::now()->format('Y');
        //Quote data for chart
        $data['quotesDataRate'] = $this->quoteKPIService->getQuotesDataRate($CurentYear);
        //Quote data for chart
        $data['quoteMonthlyRecap'] = $this->quoteKPIService->getQuotesrMonthlyRecap($CurentYear);

        return view('workflow/quotes-index')->with('data',$data);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\View\View
     */
    public function show(Quotes $id)
    {
        $CompanieSelect = $this->SelectDataService->getCompanies();
        $AddressSelect = $this->SelectDataService->getAddress();
        $ContactSelect = $this->SelectDataService->getContact();
        $AccountingConditionSelect = $this->SelectDataService->getAccountingPaymentConditions();
        $AccountingMethodsSelect = $this->SelectDataService->getAccountingPaymentMethod();
        $AccountingDeleveriesSelect =  $this->SelectDataService->getAccountingDelivery();
        $QuoteCalculatorService = new QuoteCalculatorService($id);
        $totalPrice = $QuoteCalculatorService->getTotalPrice();
        $subPrice = $QuoteCalculatorService->getSubTotal();
        $vatPrice = $QuoteCalculatorService->getVatTotal();
        $TotalServiceProductTime = $QuoteCalculatorService->getTotalProductTimeByService();
        $TotalServiceSettingTime = $QuoteCalculatorService->getTotalSettingTimeByService();
        $TotalServiceCost = $QuoteCalculatorService->getTotalCostByService();
        $TotalServicePrice = $QuoteCalculatorService->getTotalPriceByService();
        list($previousUrl, $nextUrl) = $this->getNextPrevious(new Quotes(), $id->id);
        $CustomFields = $this->customFieldService->getCustomFieldsWithValues('quote', $id->id);


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
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
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
