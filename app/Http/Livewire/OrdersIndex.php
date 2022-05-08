<?php

namespace App\Http\Livewire;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Admin\Factory;
use App\Models\Workflow\Orders;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingPaymentConditions;
use Illuminate\Support\Facades\Auth;

class OrdersIndex extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $sortField = 'created_at'; // default sorting field
    public $sortAsc = false; // default sort direction
    
    public $userSelect = [];
    public $LastOrder = '1';

    public $code; 
    public $label; 
    public $customer_reference;
    public $companies_id; 
    public $companies_contacts_id;   
    public $companies_addresses_id;  
    public $validity_date;  
    public $statu = '1';  
    public $user_id ;
    public $accounting_payment_conditions_id;  
    public $accounting_payment_methods_id;  
    public $accounting_deliveries_id;  
    public $comment;

    public $idCompanie = '';

    // Validation Rules
    protected $rules = [
        'code' =>'required|unique:orders',
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
        $this->LastOrder =  Orders::orderBy('id', 'desc')->first();
        if($this->LastOrder == Null){
            $this->code = "OR-0";
            $this->label = "OR-0";
        }
        else{
            $this->code = "OR-". $this->LastOrder->id;
            $this->label = "OR-". $this->LastOrder->id;
        }
    }

    public function render()
    {
        if(is_numeric($this->idCompanie)){
            $Orders = Orders::withCount('OrderLines')
                            ->where('companies_id', $this->idCompanie)
                            ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                            ->paginate(15);
        }
        else{
            $Orders = Orders::withCount('OrderLines')
                            ->where('label','like', '%'.$this->search.'%')
                            ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                            ->paginate(15);
        }

        $userSelect = User::select('id', 'name')->get();
        $CompanieSelect = Companies::select('id', 'code','label')->get();
        $AddressSelect = CompaniesAddresses::select('id', 'label','adress')->where('companies_id', $this->companies_id)->get();
        $ContactSelect = CompaniesContacts::select('id', 'first_name','name')->where('companies_id', $this->companies_id)->get();
        $AccountingConditionSelect = AccountingPaymentConditions::select('id', 'code','label')->get();
        $AccountingMethodsSelect = AccountingPaymentMethod::select('id', 'code','label')->get();
        $AccountingDeleveriesSelect = AccountingDelivery::select('id', 'code','label')->get();
        $Factory = Factory::first();

        return view('livewire.orders-index', [
            'Orderslist' => $Orders,
            'CompanieSelect' => $CompanieSelect,
            'AddressSelect' => $AddressSelect,
            'ContactSelect' => $ContactSelect,
            'userSelect' => $userSelect,
            'AccountingConditionSelect' => $AccountingConditionSelect,
            'AccountingMethodsSelect' => $AccountingMethodsSelect,
            'AccountingDeleveriesSelect' => $AccountingDeleveriesSelect,
            'Factory' => $Factory
        ]);
    }

    public function storeOrder(){
        $this->validate();
            // Create Line
            $OrdersCreated = Orders::create([
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
            // Reset Form Fields After Creating line
            return redirect()->route('orders.show', ['id' => $OrdersCreated->id])->with('success', 'Successfully created new order');
    }
}