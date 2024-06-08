<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use App\Models\Workflow\Orders;
use App\Models\Companies\Companies;
use Illuminate\Support\Facades\Auth;
use App\Notifications\OrderNotification;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use Illuminate\Support\Facades\Notification;
use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingPaymentConditions;

class OrdersIndex extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $sortField = 'created_at'; // default sorting field
    public $sortAsc = false; // default sort direction
    public $searchIdStatus = '';

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
    public $type = '1';
    
    public $idCompanie = '';

    // Validation Rules
    protected function rules()
    { 
        if($this->type == 1){  
            // Validation Rules
            return  [
                'code' =>'required|unique:orders',
                'label'=>'required',
                'companies_id'=>'required',
                'companies_contacts_id'=>'required',
                'companies_addresses_id'=>'required',
                'accounting_payment_conditions_id'=>'required',
                'accounting_payment_methods_id'=>'required',
                'accounting_deliveries_id'=>'required',
                'user_id'=>'required',
                'validity_date'=>'required',
            ];
        }
        elseif($this->type == 2){
            return [
                'code' =>'required|unique:orders',
                'label'=>'required',
                'user_id'=>'required',
                'validity_date'=>'required',
            ];
        }
    }

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

        $accounting_payment_conditions = AccountingPaymentConditions::select('id')->where( 'default', 1)->first(); 
        $accounting_payment_methods = AccountingPaymentMethod::select('id')->where( 'default', 1)->first(); 
        $accounting_deliveries = AccountingDelivery::select('id')->where( 'default', 1)->first(); 

        $this->accounting_payment_conditions_id = ($accounting_payment_conditions->id ?? 0); 
        $this->accounting_payment_methods_id = ($accounting_payment_methods->id  ?? 0);  
        $this->accounting_deliveries_id = ($accounting_deliveries->id  ?? 0); 

        if($this->LastOrder == Null){
            $this->code = "OR-0";
            $this->label = "OR-0";
        }
        else{
            $this->code = "OR-". $this->LastOrder->id;
            $this->label = "OR-". $this->LastOrder->id;
        }
    }

    public function changeLabel(){

        $this->userSelect = User::select('id', 'name')->get();
        $this->LastOrder = Orders::orderBy('id', 'desc')->first();

        if($this->type == 1){ 
            if($this->LastOrder == Null){
                $this->code = "OR-0";
                $this->label = "OR-0";
            }
            else{
                $this->code = "OR-". $this->LastOrder->id;
                $this->label = "OR-". $this->LastOrder->id;
            }
        }
        elseif($this->type == 2){ 
            if($this->LastOrder == Null){
                $this->code = "INT-0";
                $this->label = "INT-0";
            }
            else{
                $this->code = "INT-". $this->LastOrder->id;
                $this->label = "INT-". $this->LastOrder->id;
            }
        }
    }

    public function render()
    {
        if(is_numeric($this->idCompanie)){
            $Orders = Orders::withCount('OrderLines')
                            ->where('companies_id', $this->idCompanie)
                            ->where('statu', 'like', '%'.$this->searchIdStatus.'%')
                            ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                            ->paginate(15);
        }
        else{
            $Orders = Orders::withCount('OrderLines')
                            ->where('label','like', '%'.$this->search.'%')
                            ->where('statu', 'like', '%'.$this->searchIdStatus.'%')
                            ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                            ->paginate(15);
        }

        $userSelect = User::select('id', 'name')->get();
        $CompanieSelect = Companies::select('id', 'code','client_type','civility','label','last_name')->where('active', 1)->get();
        $AddressSelect = CompaniesAddresses::select('id', 'label','adress')->where('companies_id', $this->companies_id)->get();
        $ContactSelect = CompaniesContacts::select('id', 'first_name','name')->where('companies_id', $this->companies_id)->get();
        $AccountingConditionSelect = AccountingPaymentConditions::select('id', 'code','label')->get();
        $AccountingMethodsSelect = AccountingPaymentMethod::select('id', 'code','label')->get();
        $AccountingDeleveriesSelect = AccountingDelivery::select('id', 'code','label')->get();

        return view('livewire.orders-index', [
            'Orderslist' => $Orders,
            'CompanieSelect' => $CompanieSelect,
            'AddressSelect' => $AddressSelect,
            'ContactSelect' => $ContactSelect,
            'userSelect' => $userSelect,
            'AccountingConditionSelect' => $AccountingConditionSelect,
            'AccountingMethodsSelect' => $AccountingMethodsSelect,
            'AccountingDeleveriesSelect' => $AccountingDeleveriesSelect,
            'type' => $this->type,
        ]);
    }

    public function storeOrder(){
        $this->validate();

        if($this->type == 2){
            $this->companies_id = 0;
            $this->companies_contacts_id = 0;
            $this->companies_addresses_id = 0;
            $this->accounting_payment_conditions_id = 0;
            $this->accounting_payment_methods_id = 0;
            $this->accounting_deliveries_id = 0;
        }

        // Create Line
        $OrdersCreated = Orders::create([
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
                                        'type'=>$this->type, 
        ]);

        // notification for all user in database
        $users = User::where('orders_notification', 1)->get();
        Notification::send($users, new OrderNotification($OrdersCreated));

        if($this->type == 1){
            //change statu companie
            Companies::where('id', $this->companies_id)->update(['statu_customer'=>3]);
        }

        return redirect()->route('orders.show', ['id' => $OrdersCreated->id])->with('success', 'Successfully created new order');
    }
}