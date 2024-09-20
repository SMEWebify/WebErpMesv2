<?php

namespace App\Http\Controllers\Companies;

use App\Models\User;
use Illuminate\Support\Str;
use App\Events\QuoteCreated;
use App\Models\Workflow\Quotes;
use App\Services\CompanyService;
use App\Services\OrderKPIService;
use App\Services\QuoteKPIService;
use App\Traits\NextPreviousTrait;
use App\Models\Companies\Companies;
use App\Services\InvoiceKPIService;
use App\Services\SelectDataService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Notifications\QuoteNotification;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use Illuminate\Support\Facades\Notification;
use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Http\Requests\Companies\UpdateCompanieRequest;
use App\Models\Accounting\AccountingPaymentConditions;

class CompaniesController extends Controller
{
    use NextPreviousTrait;
    protected $SelectDataService;
    protected $orderKPIService;
    protected $quoteKPIService;
    protected $invoiceKPIService;
    protected $companyService;

    public function __construct(
        InvoiceKPIService $invoiceKPIService,
        SelectDataService $SelectDataService, 
        OrderKPIService $orderKPIService,
        QuoteKPIService $quoteKPIService,
        CompanyService $companyService,
    )
    {
        $this->SelectDataService = $SelectDataService;
        $this->orderKPIService = $orderKPIService;
        $this->quoteKPIService = $quoteKPIService;
        $this->invoiceKPIService = $invoiceKPIService;
        $this->companyService = $companyService;
    }

    protected function getCompanyCounts() 
    {
        $data['ClientCountRate'] = Companies::where('statu_customer', 2)->where('statu_supplier', '!=', 2)->count();
        $data['ProspectCountRate'] = Companies::where('statu_customer', 3)->count();
        $data['SupplierCountRate'] = Companies::where('statu_supplier', 2)->where('statu_customer', '!=', 2)->count();
        $data['ClientSupplierCountRate'] = Companies::where('statu_customer', 2)->where('statu_supplier', 2)->count();
        return $data;
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        //Quote data for chart
        $data = $this->getCompanyCounts();
        //5 lastest Companies add 
        $LastComapnies = Companies::orderBy('id', 'desc')->take(5)->get();
        return view('companies/companies-index', compact('data', 'LastComapnies'));
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\View\View
     */
    public function show(Companies $id)
    {
        $CurentYear = now()->year;
        $userSelect = $this->SelectDataService->getUsers();
        list($previousUrl, $nextUrl) = $this->getNextPrevious(new Companies(), $id->id);
        $remainingInvoiceOrder =  $this->orderKPIService->getOrderMonthlyRemainingToInvoice($id->id);
        $paidInvoices = $this->invoiceKPIService->getPaidInvoicesCount($id->id);
        $unpaidInvoices = $this->invoiceKPIService->getUnpaidInvoicesCount($id->id);
        $data['quotesDataRate'] = $this->quoteKPIService->getQuotesDataRate($CurentYear, $id->id);
        $data['orderMonthlyRecap'] = $this->orderKPIService->getOrderMonthlyRecap($CurentYear, $id->id);
        $data['orderAverage'] = $this->orderKPIService->getAverageOrderPriceAttribute($id->id);
    
        $Companie = $id;
        return view('companies/companies-show', compact('Companie', 
                                                        'userSelect', 
                                                        'previousUrl', 
                                                        'nextUrl',
                                                        'remainingInvoiceOrder',
                                                        'paidInvoices',
                                                        'unpaidInvoices',
                                                        'data',));
    }

    /**
     * @param $id
    * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateCompanieRequest $request)
    {
        $company = Companies::findOrFail($request->id);
        // Attempt to validate VAT number via SOAP service
        $vatNumber = $request->input('intra_community_vat');
        if ($vatNumber) {
            $countryCode = substr($vatNumber, 0, 2);
            $vatCode = substr($vatNumber, 2);
            try {
                $isValid = $this->companyService->validateVatNumber($countryCode, $vatCode);

                if (!$isValid) {
                   // Validation failed, but we continue the update
                    Session::flash('warning', 'Le numéro de TVA est invalide, mais les autres informations ont été mises à jour.');
                }
            } catch (\Exception $e) {
                // Catch the exception from the SOAP service
                Session::flash('error', 'Le service de validation de la TVA est indisponible. La fiche client a été mise à jour, mais sans validation du numéro de TVA.');
            }
        }

        // Update the customer record with the other fields
        $company->update($request->validated());
        // Handle specific cases outside mass assignment
        $company->active = $request->has('active') ? 1 : 0;
        $company->quoted_delivery_note = $request->has(key: 'quoted_delivery_note') ? 1 : 0;
        $company->save();

        return redirect()->route('companies.show', ['id' =>  $company->id])->with('success', 'Successfully updated companie');
    }

        /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeQuote(Companies $id)
    {
        $LastQuote =  Quotes::orderBy('id', 'desc')->first();
        if($LastQuote == Null){
            $code = "QT-0";
            $label = "QT-0";
        }
        else{
            $code = "QT-". $LastQuote->id;
            $label = "QT-". $LastQuote->id;
        }

        $accounting_payment_conditions = AccountingPaymentConditions::getDefault(); 
        $accounting_payment_methods = AccountingPaymentMethod::getDefault();  
        $accounting_deliveries = AccountingDelivery::getDefault(); 
        $defaultAddress = CompaniesAddresses::getDefault(['companies_id' => $id->id]);
        $defaultContact = CompaniesContacts::getDefault(['companies_id' => $id->id]);
        

        $accounting_payment_conditions = ($accounting_payment_conditions->id ?? 0); 
        $accounting_payment_methods = ($accounting_payment_methods->id  ?? 0);  
        $accounting_deliveries = ($accounting_deliveries->id  ?? 0);
        $defaultAddress = ($defaultAddress->id  ?? 0);
        $defaultContact = ($defaultContact->id  ?? 0);
        
        if($accounting_payment_conditions == 0 || $accounting_payment_methods == 0 || $accounting_deliveries == 0 || $defaultAddress == 0 || $defaultContact == 0){
            return redirect()->route('companies.show', ['id' =>  $id->id])->with('error', 'No default settings');
        }

        // Create Line
        $QuotesCreated = Quotes::create([
                                        'uuid'=> Str::uuid(),
                                        'code'=>$code,  
                                        'label'=>$label,  
                                        'companies_id'=>$id->id,  
                                        'companies_contacts_id'=>$defaultContact,    
                                        'companies_addresses_id'=>$defaultAddress,    
                                        'user_id'=>Auth::id(), 
                                        'accounting_payment_conditions_id'=>$accounting_payment_conditions,   
                                        'accounting_payment_methods_id'=>$accounting_payment_methods,   
                                        'accounting_deliveries_id'=>$accounting_deliveries,   
        ]);

        // notification for all user in database
        $users = User::where('quotes_notification', 1)->get();
        Notification::send($users, new QuoteNotification($QuotesCreated));

        // Trigger the event
        event(new QuoteCreated($QuotesCreated));
        return redirect()->route('quotes.show', ['id' => $QuotesCreated->id])->with('success', 'Successfully created new quote');
    }
}
