<?php

namespace App\Http\Livewire;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Workflow\Quotes;
use Illuminate\Support\Facades\DB;
use App\Models\Companies\Companies;
use App\Models\Companies\companiesContacts;
use App\Models\Companies\companiesAddresses;
use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingPaymentConditions;

class QuotesIndex extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';
    
    public $search = '';
    public $sortField = 'LABEL'; // default sorting field
    public $sortAsc = true; // default sort direction
    
    public $userSelect = [];
    public $LastQuote = '1';

    public $CODE; 
    public $LABEL; 
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

    // Validation Rules
    protected $rules = [
        'CODE' =>'required|unique:quotes',
        'LABEL'=>'required',
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
        $this->userSelect = User::select('id', 'name')->get();
        $this->LastQuote =  Quotes::latest()->first();
        if($this->LastQuote == Null){
            $this->CODE = "QT-0";
            $this->LABEL = "QT-0";
        }
        else{
            $this->CODE = "QT-". $this->LastQuote->id;
            $this->LABEL = "QT-". $this->LastQuote->id;
        }
    }

    public function render()
    {
        $Quotes = Quotes::withCount('QuoteLines')->where('LABEL','like', '%'.$this->search.'%')->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')->paginate(15);
        $CompanieSelect = Companies::select('id', 'CODE','LABEL')->get();
        $AddressSelect = companiesAddresses::select('id', 'LABEL','ADRESS')->get();
        $ContactSelect = companiesContacts::select('id', 'FIRST_NAME','NAME')->get();
        $AccountingConditionSelect = AccountingPaymentConditions::select('id', 'CODE','LABEL')->get();
        $AccountingMethodsSelect = AccountingPaymentMethod::select('id', 'CODE','LABEL')->get();
        $AccountingDeleveriesSelect = AccountingDelivery::select('id', 'CODE','LABEL')->get();
        
        return view('livewire.quotes-index',[
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
                                            'CODE'=>$this->CODE,  
                                            'LABEL'=>$this->LABEL,  
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
            // Reset Form Fields After Creating line
            return redirect()->route('quote.show', ['id' => $QuotesCreated->id])->with('success', 'Successfully created new quote');
    }
}

