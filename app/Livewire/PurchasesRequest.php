<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use App\Models\Planning\Task;
use App\Models\Planning\Status;
use Illuminate\Support\Facades\DB;
use App\Models\Companies\Companies;
use App\Models\Purchases\Purchases;
use Illuminate\Support\Facades\Auth;
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

    private $ordre = 10;

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
        $this->user_id = Auth::id();
        // get last id
        $this->LastPurchase =  Purchases::latest()->first();
        //if we have no id, define 0 
        if($this->LastPurchase == Null){
            $this->LastPurchase = 0;
            $this->code = $this->document_type ."-0";
            $this->label = $this->document_type ."-0";
        }
        // else we use is from db
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

        $this->CompaniesSelect = Companies::select('id', 'code','client_type','civility','label','last_name')->where('statu_supplier', '=', 2)->orderBy('code')->get();
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
        else{
            
            session()->flash('error', 'Please select on type of document.');
        }
    }

    public function render()
    {
        $userSelect = User::select('id', 'name')->get();
        $Status = Status::select('id')->orderBy('order')->first();
        //Select task where statu is open and only purchase type
        $PurchasesRequestsLineslist = $this->PurchasesRequestsLineslist = Task::orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                                                                        ->where('status_id', '=', $Status->id)
                                                                        ->whereNotNull('order_lines_id')
                                                                        ->where(
                                                                            function($query) {
                                                                                return $query
                                                                                    ->Where('type', '=', '2')
                                                                                    ->orWhere('type', '=', '3')
                                                                                    ->orWhere('type', '=', '4')
                                                                                    ->orWhere('type', '=', '5')
                                                                                    ->orWhere('type', '=', '6')
                                                                                    ->orWhere('type', '=', '7');
                                                                            })
                                                                            ->when($this->companies_id, function ($query) {
                                                                                return $query->whereHas('Component.preferredSuppliers', function ($supplierQuery) {
                                                                                    $supplierQuery->where('companies_id', $this->companies_id);
                                                                                });
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
            if(array_key_exists("task_id",$this->data[$key])){
                if($this->data[$key]['task_id'] != false ){
                    $i++;
                }
            }
        }

        if($i>0){
            $StatusUpdate = Status::select('id')->where('title', 'Supplied')->first();
            if(is_null($StatusUpdate)){
                $StatusUpdate = Status::select('id')->where('title', 'In progress')->first();
            }
            
            if(is_null($StatusUpdate)){
                return redirect()->back()->with('error', 'No status in kanban for define progress');
            }

            // Create puchase order
            if($this->document_type == 'PU'){
                $PurchaseOrderCreated = Purchases::create([
                    'code'=>$this->code,  
                    'label'=>$this->label, 
                    'companies_id'=>$this->companies_id,   
                    'user_id'=>$this->user_id,
                ]);

                if($PurchaseOrderCreated){
                    // Create lines
                    foreach ($this->data as $key => $item) {
                        //if not best to find request value, but we cant send hidden data with livewire
                        //How pass all information from task information ?
                        $Task = Task::find($key);

                        // Create delivery line
                        $PurchaseLines = PurchaseLines::create([
                                'purchases_id' => $PurchaseOrderCreated->id,
                                'tasks_id' => $key, 
                                'ordre' => $this->ordre, 
                                //'code' => , can be null
                                'product_id' =>$Task->component_id,
                                'label' => $Task->label,
                                //'supplier_ref' => , can be null
                                'qty' => $Task->qty,
                                'selling_price' => $Task->unit_cost,
                                'discount' => 0,
                                'unit_price_after_discount' => $Task->unit_cost,
                                'total_selling_price' => $Task->unit_cost * $Task->qty,
                                //'receipt_qty' =>, defaut to 0
                                //'invoiced_qty' =>, defaut to 0
                                'methods_units_id' => $Task->methods_units_id,
                                //'accounting_allocation_id' => , can be null
                                //'stock_locations_id' => , can be null
                                'statu' => 1
                            ]); 

                        /* // up order line for next record*/
                        $this->ordre= $this->ordre+10;
                        /* // update task statu Supplied on Kanban*/
                        if($StatusUpdate->id){
                            $Task = Task::where('id',$key)->update(['status_id'=>$StatusUpdate->id]);
                        }
                    } 
                    return redirect()->route('purchases.show', ['id' => $PurchaseOrderCreated->id])->with('success', 'Successfully created new purchase order');
                }
                else{
                    return redirect()->back()->with('error', 'Something went wrong');
                }
            }
            elseif($this->document_type == 'PQ'){
                // Create puchase quotation
                $PurchaseQuotationCreated = PurchasesQuotation::create([
                    'code'=>$this->code,  
                    'label'=>$this->label, 
                    'companies_id'=>$this->companies_id,   
                    'user_id'=>$this->user_id,
                ]);

                if($PurchaseQuotationCreated){
                    // Create lines
                    foreach ($this->data as $key => $item) {
                        $Task = Task::find($key);
                        // Create delivery line
                        $PurchaseQuotationLines = PurchaseQuotationLines::create([
                                'purchases_quotation_id' => $PurchaseQuotationCreated->id,
                                'tasks_id' => $key, 
                                'ordre' => $this->ordre, 
                                //'supplier_ref' => , can be null
                                'qty_to_order' => $Task->qty,
                                'unit_price' => $Task->unit_cost,
                                'total_price' => $Task->unit_cost * $Task->qty,
                                //'qty_accepted' =>, defaut to 0
                                //'canceled_qty' =>, defaut to 0
                            ]); 
                        /* // up order line for next record*/
                        $this->ordre= $this->ordre+10;
                    }
                    
                    return redirect()->route('purchases.quotations.show', ['id' => $PurchaseQuotationCreated->id])->with('success', 'Successfully created new purchase quotation');
                }
                else{
                    return redirect()->back()->with('error', 'Something went wrong');
                }
            }
            else{
                return redirect()->back()->with('error', 'no document type');
            }
        }
        else{
            $errors = $this->getErrorBag();
            $errors->add('errors', 'no lines selected');
        }
    }
}
