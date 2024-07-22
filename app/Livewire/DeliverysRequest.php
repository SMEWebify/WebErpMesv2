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
use Illuminate\Support\Facades\Auth;
use App\Models\Products\SerialNumbers;
use App\Models\Workflow\DeliveryLines;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Products\StockLocationProducts;

class DeliverysRequest extends Component
{

    //use WithPagination;
    //protected $paginationTheme = 'bootstrap';

    public $sortField = 'label'; // default sorting field
    public $sortAsc = true; // default sort direction
    
    public $LastDelivery = '1';

    public $DeliverysRequestsLineslist;
    public $code, $label, $companies_id, $companies_addresses_id, $companies_contacts_id, $user_id; 
    public $updateLines = false;
    public $RemoveFromStock = false;
    public $CreateSerialNumber = false;
    public $CompaniesSelect = [];
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
        $this->LastDelivery =  Deliverys::latest()->first();
        if($this->LastDelivery == Null){
            $this->code = "DN-0";
            $this->label = "DN-0";
        }
        else{
            $this->code = "DN-". $this->LastDelivery->id;
            $this->label = "DN-". $this->LastDelivery->id;
        }

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
        $this->CompaniesSelect = Companies::select('id', 'code','client_type','civility','label','last_name')
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

    public function storeDeliveryNote(){
        //check rules
        $this->validate(); 
        
        //check if line exist
        $i = 0;
        
        
        foreach ($this->data as $key => $item) {
            if(array_key_exists("order_line_id",$this->data[$key])){
                if($this->data[$key]['order_line_id'] != false ){
                    $i++;
                }
            }
        }

        if($i>0){
            // Create delivery note
            $DeliveryCreated = Deliverys::create([
                                                'uuid'=> Str::uuid(),
                                                'code'=>$this->code,  
                                                'label'=>$this->label, 
                                                'companies_id'=>$this->companies_id,   
                                                'companies_addresses_id'=>$this->companies_addresses_id,  
                                                'companies_contacts_id'=>$this->companies_contacts_id,  
                                                'user_id'=>$this->user_id, 
            ]);

            // Create delivery note lines
            foreach ($this->data as $key => $item) {
                //check if add line to new delivery note is aviable
                if(array_key_exists("order_line_id",$this->data[$key])){
                    if($this->data[$key]['order_line_id'] != false ){
                        // Create delivery line
                        $DeliveryLines = DeliveryLines::create([
                            'deliverys_id' => $DeliveryCreated->id,
                            'order_line_id' => $key, 
                            'ordre' => $this->ordre,
                            'qty' => $this->data[$key]['scumQty'],
                            'statu' => 1
                        ]); 

                        

                        // update order line info
                        //same function from stock location product controller
                        $OrderLine = OrderLines::find($key);

                        if($this->CreateSerialNumber){
                            $productId = null;
                            if($OrderLine->product_id) {
                                $productId =$OrderLine->product_id;
                            }
                            // Generate and insert serial numbers for each qt delivered
                            for ($i = 0; $i < $this->data[$key]['scumQty']; $i++) {
                                SerialNumbers::create([
                                    'products_id' => $productId,
                                    'order_line_id' => $key,
                                    'serial_number' => Str::uuid(),
                                    'status' => 2, // sold
                                ]);
                            }
                        }

                        $OrderLine->delivered_qty =  $OrderLine->delivered_qty + $this->data[$key]['scumQty'];
                        $OrderLine->delivered_remaining_qty = $OrderLine->delivered_remaining_qty - $this->data[$key]['scumQty'];
                        //if we are delivered all part
                        if($OrderLine->delivered_remaining_qty == 0){
                            $OrderLine->delivery_status = 3;
                            $OrderLine->save();
                            // update order statu info
                            // we must be check if all entry are delivered
                            event(new OrderLineUpdated($OrderLine->id));
                        }
                        else{
                            $OrderLine->delivery_status = 2;
                            $OrderLine->save();
                            // update order statu info
                            event(new OrderLineUpdated($OrderLine->id));
                        }

                        $TaskRelation = $OrderLine->Task()->get();

                        if($this->RemoveFromStock && $OrderLine->product_id && $TaskRelation->isEmpty()){
                            $quantityRemaining = $this->data[$key]['scumQty'];

                            $StockLocationProduct = StockLocationProducts::where('products_id', $OrderLine->product_id)->get();
                            foreach ($StockLocationProduct as $stock) {
                                
                                // Calculate the quantity to exit from this stock
                                $quantityToWithdraw = min($stock->getCurrentStockMove(), $quantityRemaining);
                    
                                if($quantityToWithdraw != 0){
                                    // Create a negative stock movement to record the stock issue
                                    $stockMove = StockMove::create(['user_id' => Auth::id(),
                                                                    'qty' => $quantityToWithdraw,
                                                                    'stock_location_products_id' =>  $stock->id,  
                                                                    'order_line_id' =>$OrderLine->id,
                                                                    'typ_move' =>9,
                                                                ]);
                                }
                    
                                // Update remaining quantity
                                $quantityRemaining -= $quantityToWithdraw;
                    
                                // Exit the loop if the requested quantity has been satisfied
                                if ($quantityRemaining <= 0) {
                                    break;
                                }
                            }
                        }

                        $this->ordre= $this->ordre+10;
                    }
                }  
            }
                
            // return view on new document
            return redirect()->route('deliverys.show', ['id' => $DeliveryCreated->id])->with('success', 'Successfully created new delivery note');
        }
        else{
            $errors = $this->getErrorBag();
            $errors->add('errors', 'no lines selected');
        }
    }
}
