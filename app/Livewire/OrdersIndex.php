<?php

namespace App\Livewire;

use Carbon\Carbon;
use App\Models\User;
use Livewire\Component;
use App\Events\OrderCreated;
use Livewire\WithPagination;
use App\Services\OrderService;
use App\Models\Workflow\Orders;
use App\Models\Companies\Companies;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use App\Services\DocumentCodeGenerator;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingPaymentConditions;

class OrdersIndex extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    protected $listeners = ['changeView'];

    public $viewType = 'table'; // Defaults to 'table'

    public $search = '';
    public $sortField = 'validity_date'; // default sorting field
    public $sortAsc = true; // default sort direction
    public $searchIdStatus = [1, 2];

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

    public $statuses;

    protected $orderService;
    protected $documentCodeGenerator;

    public function __construct()
    {
        // Resolve the service via the Laravel container
        $this->orderService = App::make(OrderService::class);
        $this->documentCodeGenerator = App::make(DocumentCodeGenerator::class);
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

    public function changeView($view)
    {
        $this->viewType = $view;
        session()->put('viewType', $view);
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

        $this->viewType = session()->get('viewType', 'table'); 
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
        $this->code = $this->documentCodeGenerator->generateDocumentCode($prefix, $orderId);
        $this->label = $this->code;
    }
    
    private function getPrefix($type)
    {
        switch ($type) {
            case 1:
                return 'order';
            case 2:
                return 'internal-order';
            default:
                return 'UNKNOWN';
        }
    }

    public function render()
    {
        $selectedStatuses = $this->normalizeStatuses($this->searchIdStatus);

        if(is_numeric($this->idCompanie)){
            $OrdersQuery = Orders::withCount('OrderLines')
                            ->where('companies_id', $this->idCompanie)
                            ->when($selectedStatuses, function ($query) use ($selectedStatuses) {
                                $query->whereIn('statu', $selectedStatuses);
                            });
        }
        else{
            $OrdersQuery = Orders::withCount('OrderLines')
                            ->where('label','like', '%'.$this->search.'%')
                            ->when($selectedStatuses, function ($query) use ($selectedStatuses) {
                                $query->whereIn('statu', $selectedStatuses);
                            });
        }

        $Orders = $this->applySorting($OrdersQuery)->paginate(15);
        $this->statuses = $this->buildStatuses($selectedStatuses);

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

    private function applySorting($query)
    {
        if ($this->sortField === 'validity_date') {
            return $query
                ->orderByRaw('validity_date is null')
                ->orderBy('validity_date', $this->sortAsc ? 'asc' : 'desc');
        }

        return $query->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc');
    }

    private function normalizeStatuses($statuses)
    {
        if (!is_array($statuses)) {
            return [];
        }

        return array_values(array_filter(array_map('intval', $statuses)));
    }

    private function buildStatuses(array $selectedStatuses)
    {
        $allStatuses = [
            1 => __('general_content.open_trans_key'),
            2 => __('general_content.in_progress_trans_key'),
            3 => __('general_content.delivered_trans_key'),
            4 => __('general_content.partly_delivered_trans_key'),
            5 => __('general_content.stopped_trans_key'),
            6 => __('general_content.canceled_trans_key'),
        ];

        $statusesToShow = $selectedStatuses ?: array_keys($allStatuses);

        return collect($statusesToShow)->map(function ($statusId) use ($allStatuses) {
            $query = Orders::with(['companie', 'contact'])
                ->where('statu', $statusId);

            if (in_array($statusId, [3, 4], true)) {
                $query->where('updated_at', '>=', Carbon::now()->subHours(48));
            }

            if ($this->search !== '') {
                $query->where('label', 'like', '%'.$this->search.'%');
            }

            return [
                'id' => $statusId,
                'title' => $allStatuses[$statusId],
                'Orders' => $query->get(),
            ];
        })->values()->all();
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
