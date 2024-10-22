<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use App\Events\OrderCreated;
use Livewire\WithPagination;
use App\Services\OrderService;
use App\Models\Workflow\Orders;
use App\Models\Companies\Companies;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
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
    public $LastOrder = null;

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

    protected $orderService;

    public function __construct()
    {
        // Résoudre le service via le container Laravel
        $this->orderService = App::make(OrderService::class);
    }

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
        $this->LastOrder = Orders::orderBy('id', 'desc')->first();
    
        $this->accounting_payment_conditions_id = $this->getDefaultId(AccountingPaymentConditions::class);
        $this->accounting_payment_methods_id = $this->getDefaultId(AccountingPaymentMethod::class);
        $this->accounting_deliveries_id = $this->getDefaultId(AccountingDelivery::class);
    
        $this->setOrderCodeAndLabel();
    }
    
    public function changeLabel()
    {
        $this->userSelect = User::select('id', 'name')->get();
        $this->LastOrder = Orders::orderBy('id', 'desc')->first();
        $this->setOrderCodeAndLabel();
    }
    
    private function getDefaultId($model)
    {
        $record = $model::select('id')->where('default', 1)->first();
        return $record->id ?? 0;
    }
    
    private function setOrderCodeAndLabel()
    {
        $prefix = $this->getPrefix($this->type);
        $orderId = $this->LastOrder ? $this->LastOrder->id : 0;
    
        $orderId +=1;
        $this->code = "{$prefix}-{$orderId}";
        $this->label = "{$prefix}-{$orderId}";
    }
    
    private function getPrefix($type)
    {
        switch ($type) {
            case 1:
                return 'OR';
            case 2:
                return 'INT';
            default:
                return 'UNKNOWN';
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
            $this->companies_id = null;
            $this->companies_contacts_id = null;
            $this->companies_addresses_id = null;
            $this->accounting_payment_conditions_id = null;
            $this->accounting_payment_methods_id = null;
            $this->accounting_deliveries_id = null;
        }

        // Create order
        $OrdersCreated = $this->orderService->createOrder(
            $this->code,
            $this->label,
            $this->customer_reference,
            $this->companies_id,
            $this->companies_contacts_id,
            $this->companies_addresses_id,
            $this->validity_date,
            $this->statu,
            $this->user_id,
            $this->accounting_payment_conditions_id,
            $this->accounting_payment_methods_id,
            $this->accounting_deliveries_id,
            $this->comment,
            $this->type,
            null,
            null
        );

        if($this->type == false){
            // Trigger the event
            event(new OrderCreated($OrdersCreated));
        }

        return redirect()->route('orders.show', ['id' => $OrdersCreated->id])->with('success', 'Successfully created new order');
    }
}