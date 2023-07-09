<?php

namespace App\Http\Livewire;

use App\Models\User;
use Livewire\Component;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Deliverys;
use App\Models\Companies\Companies;
use App\Models\Workflow\OrderLines;
use Illuminate\Support\Facades\Auth;
use App\Models\Workflow\DeliveryLines;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;

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
        $this->CompaniesSelect = Companies::select('id', 'label', 'code')->orderBy('code')->get();
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
                            'order_line_id' => $this->data[$key]['order_line_id'], 
                            'ordre' => $this->ordre,
                            'qty' => $this->data[$key]['scumQty'],
                            'statu' => 1
                        ]); 

                        // update order line info
                        $OrderLine = OrderLines::find($this->data[$key]['order_line_id']);
                        $OrderLine->delivered_qty =  $OrderLine->delivered_qty + $this->data[$key]['scumQty'];
                        $OrderLine->delivered_remaining_qty = $OrderLine->delivered_remaining_qty - $this->data[$key]['scumQty'];
                        //if we are delivered all part
                        if($OrderLine->delivered_remaining_qty == 0){
                            $OrderLine->delivery_status = 3;
                            // update order statu info
                            // we must be check if all entry are delivered
                            //Orders::where('id',$OrderLine->orders_id)->update(['statu'=>2]);
                        }
                        else{
                            $OrderLine->delivery_status = 2;
                            // update order statu info
                            Orders::where('id',$OrderLine->orders_id)->update(['statu'=>3]);
                        }
                        $OrderLine->save();

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
