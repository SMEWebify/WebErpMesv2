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
use App\Models\Workflow\QuoteProjectEstimate;
use App\Http\Requests\Workflow\UpdateQuoteRequest;
use App\Http\Requests\Workflow\ProjectEstimateRequest;

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
        $AddressSelect = $this->SelectDataService->getAddress($id->companies_id);
        $ContactSelect = $this->SelectDataService->getContact($id->companies_id);
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
        $projectEstimate = QuoteProjectEstimate::where('quotes_id', $id->id)->first();
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
            'projectEstimate' => $projectEstimate,
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

    public function saveProjectEstimate(ProjectEstimateRequest $request, $quoteId)
    {
        // Récupérer les données validées
        $validated = $request->validated();

        // Recherche ou création d'une nouvelle estimation de projet
        $projectEstimate = QuoteProjectEstimate::where('quotes_id', $quoteId)->first();

        // Si l'estimation de projet n'existe pas, on en crée une nouvelle
        if (!$projectEstimate) {
            $projectEstimate = new QuoteProjectEstimate();
            $projectEstimate->quotes_id = $quoteId; // Assigne le quote_id à la nouvelle instance
        }

        // Mises à jour des champs avec les valeurs soumises dans le formulaire
        $projectEstimate->show_client_requirements_on_pdf = $request->has('show_client_requirements_on_pdf') ? 1 : 2;
        $projectEstimate->show_layout_on_pdf = $request->has('show_layout_on_pdf') ? 1 : 2;
        $projectEstimate->show_materials_on_pdf = $request->has('show_materials_on_pdf') ? 1 : 2;
        $projectEstimate->show_logistics_on_pdf = $request->has('show_logistics_on_pdf') ? 1 : 2;
        $projectEstimate->show_contractors_on_pdf = $request->has('show_contractors_on_pdf') ? 1 : 2;
        $projectEstimate->show_waste_on_pdf = $request->has('show_waste_on_pdf') ? 1 : 2;
        $projectEstimate->show_taxes_on_pdf = $request->has('show_taxes_on_pdf') ? 1 : 2;
        $projectEstimate->show_options_on_pdf = $request->has('show_options_on_pdf') ? 1 : 2;
        $projectEstimate->show_insurance_on_pdf = $request->has('show_insurance_on_pdf') ? 1 : 2;

        // Sauvegarde des données validées dans l'estimation de projet
        $projectEstimate->fill($validated); // Mise à jour des champs avec les données validées

        if ($projectEstimate->exists) {
            // Mise à jour de l'estimation existante
            $projectEstimate->save();
        } else {
            // Création d'une nouvelle estimation si elle n'existe pas encore
            $projectEstimate->save();
        }


    // Redirection après l'enregistrement
    return redirect()->route('quotes.show', ['id' => $quoteId])
        ->with('success', __('Estimation de projet enregistrée avec succès.'));
}
}
