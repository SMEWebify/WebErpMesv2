<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use App\Models\Planning\Task;
use App\Models\Planning\Status;
use App\Models\Companies\Companies;
use Illuminate\Support\Facades\Auth;
use App\Models\Planning\TaskActivities;
use App\Models\Purchases\PurchaseLines;
use App\Models\Purchases\PurchaseReceipt;
use App\Models\Purchases\PurchaseReceiptLines;

class PurchasesWaintingReceipt extends Component
{
    public $companies_id = '';
    public $sortField = 'id'; // default sorting field
    public $sortAsc = true; // default sort direction
    
    public $LastReceipt= '0';
    public $document_type = 'RC';
    public $deliveryNoteNumber;

    public $PurchasesWaintingReceiptLineslist;
    public $code, $label, $user_id; 
    public $updateLines = false;
    public $CompaniesSelect = [];
    public $data = [];
    public $qty = [];
    
    private $ordre = 10;

    // Validation Rules
    protected function rules()
    { 
        return [
            'code' =>'required|unique:purchase_receipts',
            'label' =>'required',
            'companies_id'=>'required',
            'user_id'=>'required',
        ];
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
        $this->LastReceipt =  PurchaseReceipt::latest()->first();
        //if we have no id, define 0 
        if($this->LastReceipt == Null){
            $this->LastReceipt = 0;
            $this->code = $this->document_type ."-0";
            $this->label = $this->document_type ."-0";
        }
        // else we use is from db
        else{
            $this->LastReceipt = $this->LastReceipt->id;
            $this->code = $this->document_type ."-". $this->LastReceipt;
            $this->label = $this->document_type ."-". $this->LastReceipt;
        }

        $this->CompaniesSelect = Companies::select('id', 'label', 'code')->where('statu_supplier', '=', 2)->orderBy('code')->get();
    }

    public function render()
    {
        $userSelect = User::select('id', 'name')->get();
        //Select task where statu is open and only purchase type
        $PurchasesWaintingReceiptLineslist = $this->PurchasesWaintingReceiptLineslist = PurchaseLines::orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                                                                                        ->where('receipt_qty','<=', 'qty')
                                                                                        ->whereHas('purchase', function($q){
                                                                                            $q->where('companies_id','like', '%'.$this->companies_id.'%');
                                                                                        })
                                                                                        ->get();

        return view('livewire.purchases-wainting-receipt', [
            'PurchasesWaintingReceiptLineslist' => $PurchasesWaintingReceiptLineslist,
            'userSelect' => $userSelect,
        ]);
    }

    public function storeReciep(){
        //check rules
        $this->validate(); 

         //check if line exist
        $i = 0;
        foreach ($this->data as $key => $item) {
            if(array_key_exists("purchase_line_id",$this->data[$key])){
                if($this->data[$key]['purchase_line_id'] != false ){
                    $i++;
                }
            }
        }

        if($i>0){
            $StatusUpdate = Status::select('id')->where('title', 'Finished')->first();
            if(is_null($StatusUpdate)){
                return redirect()->back()->with('error', 'No status in kanban for define finiched task');
            }
            // Create puchase order
            $ReceiptCreated = PurchaseReceipt::create([
                'code'=>$this->code,  
                'label'=>$this->label, 
                'companies_id'=>$this->companies_id,
                'delivery_note_number'=>$this->deliveryNoteNumber, 
                'user_id'=>$this->user_id,
            ]);

            // Create lines
            foreach ($this->data as $key => $item) {
                //check if add line to new receipt line is aviable
                //if not best to find request value, but we cant send hidden data with livewire
                //How pass all information from task information ?
                $PurchaseLines = PurchaseLines::find($key);
                
                // Create delivery line
                $ReceiptLines = PurchaseReceiptLines::create([
                    'purchase_receipt_id' => $ReceiptCreated->id, 
                    'purchase_line_id' => $PurchaseLines->id, 
                    'ordre' => $this->ordre, 
                    'receipt_qty' => $PurchaseLines->qty, 
                ]); 
                /* // up order line for next record*/
                $this->ordre= $this->ordre+10;
                /* // update statu line of purchase order line*/
                PurchaseLines::where('id',$PurchaseLines->id)->update(['receipt_qty'=>$PurchaseLines->qty]);
                /* // update task statu Supplied on Kanban*/
                if($StatusUpdate->id){
                    $Task = Task::where('id',$PurchaseLines->tasks_id)->update(['status_id'=>$StatusUpdate->id]);
                }

                //create entry qty int task
                TaskActivities::create([
                    'task_id'=> $PurchaseLines->tasks_id,
                    'user_id'=>$this->user_id,
                    'type'=>'4',
                    'good_qt'=>$PurchaseLines->qty,
                    'comment'=>'',
                ]);
            } 

            return redirect()->route('purchase.receipts.show', ['id' => $ReceiptCreated->id])->with('success', 'Successfully created new receipt');
        }
        else{
            $errors = $this->getErrorBag();
            $errors->add('errors', 'no lines selected');
        }
    }
}