<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Str;
use App\Events\QuoteCreated;
use Livewire\WithPagination;
use App\Models\Workflow\Quotes;
use App\Models\Companies\Companies;
use Illuminate\Support\Facades\Auth;
use App\Notifications\QuoteNotification;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use Illuminate\Support\Facades\Notification;
use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingPaymentConditions;

class QuotesIndex extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';
    
    public $search = '';
    public $sortField = 'created_at'; // default sorting field
    public $sortAsc = false; // default sort direction
    public $searchIdStatus = '';
    
    public $userSelect = [];
    public $LastQuote = null;

    //Quote
    public $code; 
    public $label; 
    public $customer_reference;
    public $companies_id; 
    public $companies_contacts_id;   
    public $companies_addresses_id;  
    public $validity_date;  
    public $statu = '1'; 
    public $user_id;  
    public $accounting_payment_conditions_id;  
    public $accounting_payment_methods_id;  
    public $accounting_deliveries_id;  
    public $comment;

    //adresse
    public $CustomerAddressesOrdre, $CustomerAddressesLabel, $CustomerAdress, $CustomerZipcode, $CustomerCity, $CustomerCountry, $CustomerAddressesNumber, $CustomerAddressesMail;   
    //Contact
    public $CustomerContactOrdre, $CustomerCivility, $CustomerFirstName, $CustomerName, $CustomerFunction, $CustomerContactNumber, $CustomerMobile, $CustomerContactMail;   
    

    public $idCompanie = '';

    // Validation Rules
    protected $rules = [
        'code' =>'required|unique:quotes',
        'label'=>'required',
        'companies_id'=>'required',
        'companies_contacts_id'=>'required',
        'companies_addresses_id'=>'required',
        'accounting_payment_conditions_id'=>'required',
        'accounting_payment_methods_id'=>'required',
        'accounting_deliveries_id'=>'required',
        'user_id'=>'required',
    ];

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortAsc = !$this->sortAsc; 
        } else {
            $this->sortAsc = true; 
        }
        $this->sortField = $field;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function mount()
    {
        $this->user_id = Auth::id();
        $this->userSelect = User::select('id', 'name')->get();
        $this->LastQuote = Quotes::orderBy('id', 'desc')->first();
    
        $this->accounting_payment_conditions_id = $this->getDefaultId(AccountingPaymentConditions::class);
        $this->accounting_payment_methods_id = $this->getDefaultId(AccountingPaymentMethod::class);
        $this->accounting_deliveries_id = $this->getDefaultId(AccountingDelivery::class);
    
        $this->setQuoteCodeAndLabel();
    }
    
    private function getDefaultId($model)
    {
        $record = $model::select('id')->where('default', 1)->first();
        return $record->id ?? 0;
    }
    
    private function setQuoteCodeAndLabel()
    {
        $quoteId = $this->LastQuote ? $this->LastQuote->id : 0;
        $this->code = "QT-{$quoteId}";
        $this->label = "QT-{$quoteId}";
    }

    public function render()
    {
        if(is_numeric($this->idCompanie)){
            $Quotes = Quotes::withCount('QuoteLines')
                            ->where('companies_id', $this->idCompanie)
                            ->where('statu', 'like', '%'.$this->searchIdStatus.'%')
                            ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                            ->paginate(15);
        }
        else{
            $Quotes = Quotes::withCount('QuoteLines')
                            ->where('label','like', '%'.$this->search.'%')
                            ->where('statu', 'like', '%'.$this->searchIdStatus.'%')
                            ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                            ->paginate(15);
        }

        
        $CompanieSelect = Companies::select('id', 'code','client_type','civility','label','last_name')->where('active', 1)->get();
        $AddressSelect = CompaniesAddresses::select('id', 'label','adress')->where('companies_id', $this->companies_id)->get();
        $ContactSelect = CompaniesContacts::select('id', 'first_name','name')->where('companies_id', $this->companies_id)->get();
        $AccountingConditionSelect = AccountingPaymentConditions::select('id', 'code','label', 'default')->get();
        $AccountingMethodsSelect = AccountingPaymentMethod::select('id', 'code','label', 'default')->get();
        $AccountingDeleveriesSelect = AccountingDelivery::select('id', 'code','label', 'default')->get();

        return view('livewire.quotes-index')->with([
            'Quoteslist' => $Quotes,
            'CompanieSelect' => $CompanieSelect,
            'AddressSelect' => $AddressSelect,
            'ContactSelect' => $ContactSelect,
            'AccountingConditionSelect' => $AccountingConditionSelect,
            'AccountingMethodsSelect' => $AccountingMethodsSelect,
            'AccountingDeleveriesSelect' => $AccountingDeleveriesSelect,
        ]);
    }
    
    public function storeQuote(){
        $this->validate();
            // Create Line
            $QuotesCreated = Quotes::create([
                                            'uuid'=> Str::uuid(),
                                            'code'=>$this->code,  
                                            'label'=>$this->label,  
                                            'customer_reference'=>$this->customer_reference, 
                                            'companies_id'=>$this->companies_id,  
                                            'companies_contacts_id'=>$this->companies_contacts_id,    
                                            'companies_addresses_id'=>$this->companies_addresses_id,   
                                            'validity_date'=>$this->validity_date,   
                                            'statu'=>$this->statu,   
                                            'user_id'=>$this->user_id,   
                                            'accounting_payment_conditions_id'=>$this->accounting_payment_conditions_id,   
                                            'accounting_payment_methods_id'=>$this->accounting_payment_methods_id,   
                                            'accounting_deliveries_id'=>$this->accounting_deliveries_id,   
                                            'comment'=>$this->comment, 
            ]);

            // notification for all user in database
            $users = User::where('quotes_notification', 1)->get();
            Notification::send($users, new QuoteNotification($QuotesCreated));

            // Trigger the event
            event(new QuoteCreated($QuotesCreated));
            return redirect()->route('quotes.show', ['id' => $QuotesCreated->id])->with('success', 'Successfully created new quote');
    }

    public function saveCutomerAddresses(){
            $validatedData = $this->validate([
                'companies_id' => 'required',
                'CustomerAddressesOrdre' => 'required',
                'CustomerAddressesLabel' => 'required',
            ]);

            $adress = CompaniesAddresses::create([
                                        'companies_id'=> $this->companies_id,
                                        'ordre'=>$this->CustomerAddressesOrdre,  
                                        'label'=>$this->CustomerAddressesLabel,  
                                        'adress'=>$this->CustomerAdress, 
                                        'zipcode'=>$this->CustomerZipcode,  
                                        'city'=>$this->CustomerCity,    
                                        'country'=>$this->CustomerCountry,   
                                        'number'=>$this->CustomerAddressesNumber,   
                                        'mail'=>$this->CustomerAddressesMail, 
                            ]); 
         // Set Flash Message
        session()->flash('success','Line added Successfully');
    }

    public function saveCutomerContact(){
        $validatedData = $this->validate([
            'companies_id' => 'required',
            'CustomerContactOrdre' => 'required',
            'CustomerCivility' => 'required',
            'CustomerFirstName' => 'required',
            'CustomerName' => 'required',
        ]);

        $contact = CompaniesContacts::create([
                                    'companies_id'=> $this->companies_id,
                                    'ordre'=>$this->CustomerContactOrdre,  
                                    'civility'=>$this->CustomerCivility,  
                                    'first_name'=>$this->CustomerFirstName, 
                                    'name'=>$this->CustomerName,  
                                    'function'=>$this->CustomerFunction,    
                                    'number'=>$this->CustomerContactNumber,    
                                    'mobile'=>$this->CustomerMobile,    
                                    'mail'=>$this->CustomerContactMail, 
                        ]); 

        // Set Flash Message
        session()->flash('success','Line added Successfully');
    }
}

