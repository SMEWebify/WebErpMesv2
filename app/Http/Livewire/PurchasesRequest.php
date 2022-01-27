<?php

namespace App\Http\Livewire;

use App\Models\User;
use Livewire\Component;
use App\Models\Planning\Task;
use App\Models\Companies\Companies;
use App\Models\Purchases\Purchases;
use App\Models\Purchases\PurchaseLines;
use App\Models\Purchases\PurchasesQuotation;
use App\Models\Purchases\PurchaseQuotationLines;

class PurchasesRequest extends Component
{
    //use WithPagination;
    //protected $paginationTheme = 'bootstrap';

    public $companies_id = '';
    public $sortField = 'tasks.id'; // default sorting field
    public $sortAsc = true; // default sort direction
    
    public $LastPurchase = '0';
    public $LastPurchaseQuotation = '0';
    public $document_type = 'PU';
    public $document_type_label = 'PU';

    public $PurchasesRequestsLineslist;
    public $code, $label, $user_id; 
    public $updateLines = false;
    public $CompaniesSelect = [];
    public $data = [];
    public $qty = [];

    private $ORDRE = 10;

    // Validation Rules
    protected function rules()
    { 
        if($this->document_type == 'PU'){  
            return [
                'code' =>'required|unique:purchases',
                'label' =>'required',
                'companies_id'=>'required',
                'user_id'=>'required',
            ];
        }
        elseif($this->document_type == 'PQ'){
            return [
                'code' =>'required|unique:purchases_quotations',
                'label' =>'required',
                'companies_id'=>'required',
                'user_id'=>'required',
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

    public function mount() 
    {
            $this->LastPurchase =  Purchases::latest()->first();
            if($this->LastPurchase == Null){
                $this->code = $this->document_type ."-0";
                $this->label = $this->document_type ."-0";
            }
            else{
                $this->LastPurchase = $this->LastPurchase->id;
                $this->code = $this->document_type ."-". $this->LastPurchase;
                $this->label = $this->document_type ."-". $this->LastPurchase;
            }

            $this->LastPurchaseQuotation =  PurchasesQuotation::latest()->first();
            if($this->LastPurchaseQuotation == Null){
                $this->LastPurchaseQuotation = 0;
            }
            else{
                $this->LastPurchaseQuotation = $this->LastPurchaseQuotation->id;
            }

        $this->CompaniesSelect = Companies::select('id', 'label', 'code')->where('statu_supplier', '=', 2)->orderBy('code')->get();
    }
    
    public function changeDocument() 
    {
        if($this->document_type == 'PU'){ 
            $this->code = $this->document_type ."-". $this->LastPurchase;
            $this->label = $this->document_type ."-". $this->LastPurchase;
        }
        elseif($this->document_type == 'PQ'){
            $this->code = $this->document_type ."-". $this->LastPurchaseQuotation;
            $this->label = $this->document_type ."-". $this->LastPurchaseQuotation;
        }
    }

    public function render()
    {
        $userSelect = User::select('id', 'name')->get();

        //Select task where statu is open and only purchase type
        $PurchasesRequestsLineslist = $this->PurchasesRequestsLineslist = Task::orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                                                                        ->where('status_id', '=', '6')
                                                                        ->where(
                                                                            function($query) {
                                                                                return $query
                                                                                    ->where('type', '=', '1')
                                                                                    ->orWhere('type', '=', '2')
                                                                                    ->orWhere('type', '=', '3')
                                                                                    ->orWhere('type', '=', '4')
                                                                                    ->orWhere('type', '=', '5')
                                                                                    ->orWhere('type', '=', '6')
                                                                                    ->orWhere('type', '=', '7');
                                                                            })->get();
        return view('livewire.purchases-request', [
            'PurchasesRequestsLineslist' => $PurchasesRequestsLineslist,
            'userSelect' => $userSelect,
        ]);
    }

    public function storePurchase(){
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
            // Create puchase order
            if($this->document_type == 'PU'){
                $PurchaseOrderCreated = Purchases::create([
                    'code'=>$this->code,  
                    'label'=>$this->label, 
                    'companies_id'=>$this->companies_id,   
                    'user_id'=>$this->user_id,
                ]);

                // Create lines
                foreach ($this->data as $key => $item) {
                    //check if add line to new delivery note is aviable
                    if(array_key_exists("order_line_id",$this->data[$key])){
                        if($this->data[$key]['order_line_id'] != false ){
                            //if not best to find request value, but we cant send hidden data with livewire
                            //How pass all information from task information ?
                           // $Task = Task::find($this->data[$key]['deliverys_id']);
                            // Create delivery line
                            
                            $PurchaseLines = PurchaseLines::create([
                                'purchases_id' => $PurchaseOrderCreated->id,
                                'order_line_id' => $PurchaseOrderCreated->order_line_id, 
                                'delivery_line_id' => $this->data[$key]['deliverys_id'], 
                                'ORDRE' => $this->ORDRE,
                                'qty' => $PurchaseOrderCreated->qty,
                                'statu' => 1
                            ]); 

                        /* // update order line info
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
                            $OrderLine->save();*/

                            $this->ORDRE= $this->ORDRE+10;
                        }
                    }  
                }
                return redirect()->route('purchase.show', ['id' => $PurchaseOrderCreated->id])->with('success', 'Successfully created new purchase order');
            }
            elseif($this->document_type == 'PQ'){
                // Create puchase quotation
                $PurchaseQuotationCreated = PurchasesQuotation::create([
                    'code'=>$this->code,  
                    'label'=>$this->label, 
                    'companies_id'=>$this->companies_id,   
                    'user_id'=>$this->user_id,
                ]);

                return redirect()->route('purchase.quotation.show', ['id' => $PurchaseQuotationCreated->id])->with('success', 'Successfully created new purchase quotation');
            }
            else{
                return redirect()->route('purchases-request')->with('error', 'no document type');
            }
        }
        else{
            return redirect()->route('purchases-request')->with('error', 'no lines selected');
        }
    }
}
