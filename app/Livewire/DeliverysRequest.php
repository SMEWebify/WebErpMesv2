<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Str;
use App\Events\OrderLineUpdated;
use App\Models\Products\StockMove;
use App\Models\Workflow\Deliverys;
use App\Models\Companies\Companies;
use App\Models\Workflow\OrderLines;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use App\Services\DeliveryLineService;
use App\Models\Products\SerialNumbers;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Products\StockLocationProducts;

class DeliverysRequest extends Component
{
    protected $deliveryLineService;
    
    public function __construct()
    {
        // Résoudre le service via le container Laravel
        $this->deliveryLineService = App::make(DeliveryLineService::class);
    }

    //use WithPagination;
    //protected $paginationTheme = 'bootstrap';

    public $sortField = 'label'; // default sorting field
    public $sortAsc = true; // default sort direction
    
    public $LastDelivery = null;

    public $DeliverysRequestsLineslist;
    public $code, $label, $companies_id, $companies_addresses_id, $companies_contacts_id, $user_id; 
    public $updateLines = false;
    public $RemoveFromStock = false;
    public $CreateSerialNumber = false;
    public $CompanieSelect = [];
    public $data = [];
    public $qty = [];

    private $ordre = 10;

    // Validation Rules
    protected $rules = [
        'code' =>'required|unique:deliverys',
        'label' =>'required',
        'companies_id'=>'required',
        'companies_addresses_id' =>'required',
        'companies_contacts_id' =>'required',
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

    public function mount() 
    {
        $this->user_id = Auth::id();
        $this->LastDelivery = Deliverys::latest()->first();
        $this->code = $this->LastDelivery ? "DN-" . $this->LastDelivery->id : "DN-0";
        $this->label = $this->code;
    }

    public function render()
    {
        $userSelect = User::select('id', 'name')->get();

        // Get the unique IDs of the companies in the order list
        $companyIdsInOrderLines = OrderLines::where(function ($query) {
                                                $query->where('delivery_status', '=', '1')
                                                    ->orWhere('delivery_status', '=', '2');
                                            })
                                            ->leftJoin('orders', 'order_lines.orders_id', '=', 'orders.id')
                                            ->pluck('orders.companies_id')
                                            ->unique();

        // Filter companies based on unique IDs
        $this->CompanieSelect = Companies::select('id', 'code','client_type','civility','label','last_name')
                                            ->whereIn('id', $companyIdsInOrderLines)
                                            ->orderBy('code')
                                            ->get();
        

        $AddressSelect = CompaniesAddresses::select('id', 'label','adress')->where('companies_id', $this->companies_id)->get();
        $ContactSelect = CompaniesContacts::select('id', 'first_name','name')->where('companies_id', $this->companies_id)->get();
        
        //Select order line where not delivered or partialy delivered
        $DeliverysRequestsLineslist = $this->DeliverysRequestsLineslist = OrderLines::orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                                                                        ->where(
                                                                            function($query) {
                                                                                return $query
                                                                                    ->where('delivery_status', '=', '1')
                                                                                    ->orWhere('delivery_status', '=', '2');
                                                                            })
                                                                        ->whereHas('order', function($q){
                                                                            $q->where('companies_id','like', '%'.$this->companies_id.'%')
                                                                                ->where('type', '=', '1');
                                                                        })->get();

        

        return view('livewire.deliverys-request', [
            'DeliverysRequestsLineslist' => $DeliverysRequestsLineslist,
            'AddressSelect' => $AddressSelect,
            'ContactSelect' => $ContactSelect,
            'userSelect' => $userSelect,
        ]);
    }

    public function storeDeliveryNote()
    {
        // Validate the request
        $this->validate();
    
        // Check if any order line exists
        $orderLineExists = $this->checkOrderLineExists();
    
        if ($orderLineExists) {
            // Create delivery note
            $deliveryCreated = $this->createDeliveryNote();
    
            // Create delivery note lines
            $this->createDeliveryNoteLines($deliveryCreated);
    
            // Redirect to the newly created delivery note
            return redirect()->route('deliverys.show', ['id' => $deliveryCreated->id])
                            ->with('success', 'Successfully created new delivery note');
        } else {
            $errors = $this->getErrorBag();
            $errors->add('errors', 'No lines selected');
        }
    }
    
    private function checkOrderLineExists()
    {
        foreach ($this->data as $item) {
            if ($this->isOrderLineValid($item)) {
                return true;
            }
        }
        return false;
    }
    
    private function createDeliveryNote()
    {
        return Deliverys::create([
            'uuid' => Str::uuid(),
            'code' => $this->code,
            'label' => $this->label,
            'companies_id' => $this->companies_id,
            'companies_addresses_id' => $this->companies_addresses_id,
            'companies_contacts_id' => $this->companies_contacts_id,
            'user_id' => $this->user_id,
        ]);
    }
    
    private function createDeliveryNoteLines($deliveryCreated)
    {
        foreach ($this->data as $key => $item) {
            if ($this->isOrderLineValid($item)) {
                $this->deliveryLineService->createDeliveryLine($deliveryCreated, $key, $this->ordre, $item['scumQty']);
                $this->updateOrderLine($key, $item['scumQty']);
                $this->handleStockMovement($key, $item['scumQty']);
                $this->ordre += 10;
            }
        }
    }
    
    private function isOrderLineValid($item)
    {
        return array_key_exists('order_line_id', $item) && $item['order_line_id'] !== false && !empty($item['scumQty']);
    }
    
    private function updateOrderLine($orderLineId, $scumQty)
    {
        $orderLine = OrderLines::find($orderLineId);
    
        if ($this->CreateSerialNumber) {
            $this->generateSerialNumbers($orderLine->product_id, $orderLineId, $scumQty);
        }
    
        $orderLine->delivered_qty += $scumQty;
        $orderLine->delivered_remaining_qty -= $scumQty;
    
        if ($orderLine->delivered_remaining_qty == 0) {
            $orderLine->delivery_status = 3;
        } else {
            $orderLine->delivery_status = 2;
        }
    
        $orderLine->save();
        event(new OrderLineUpdated($orderLine->id));
    }
    
    private function generateSerialNumbers($productId, $orderLineId, $scumQty)
    {
        for ($i = 0; $i < $scumQty; $i++) {
            SerialNumbers::create([
                'products_id' => $productId,
                'order_line_id' => $orderLineId,
                'serial_number' => Str::uuid(),
                'status' => 2, // sold
            ]);
        }
    }
    
    private function handleStockMovement($orderLineId, $scumQty)
    {
        $orderLine = OrderLines::find($orderLineId);
        $taskRelation = $orderLine->Task()->get();
    
        if ($this->RemoveFromStock && $orderLine->product_id && $taskRelation->isEmpty()) {
            $quantityRemaining = $scumQty;
            $stockLocationProducts = StockLocationProducts::where('products_id', $orderLine->product_id)->get();
    
            foreach ($stockLocationProducts as $stock) {
                $quantityToWithdraw = min($stock->getCurrentStockMove(), $quantityRemaining);
    
                if ($quantityToWithdraw != 0) {
                    StockMove::create([
                        'user_id' => Auth::id(),
                        'qty' => $quantityToWithdraw,
                        'stock_location_products_id' => $stock->id,
                        'order_line_id' => $orderLine->id,
                        'typ_move' => 9,
                    ]);
                }
    
                $quantityRemaining -= $quantityToWithdraw;
    
                if ($quantityRemaining <= 0) {
                    break;
                }
            }
        }
    }
}
