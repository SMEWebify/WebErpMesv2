<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use App\Models\Companies\Companies;
use Illuminate\Support\Facades\Auth;
use App\Models\Purchases\PurchaseLines;
use Illuminate\Support\Facades\Redirect;
use App\Models\Purchases\PurchaseInvoice;
use App\Models\Purchases\PurchaseInvoiceLines;
use App\Models\Purchases\PurchaseReceiptLines;


class PurchasesWaintingInvoice extends Component
{
    public $companies_id = '';
    public $sortField = 'id'; // default sorting field
    public $sortAsc = true; // default sort direction
    
    public $label;
    public $LastInvoice= '0';
    public $document_type = 'PU-IN';

    public $PurchasesWaintingInvoiceLineslist;
    public $code, $user_id; 
    public $updateLines = false;
    public $CompaniesSelect = [];
    public $data = [];

    // Validation Rules
    protected function rules()
    { 
        return [
            'code' =>'required|unique:purchase_invoices',
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
        $this->LastInvoice =  PurchaseInvoice::latest()->first();
        //if we have no id, define 0 
        if($this->LastInvoice == Null){
            $this->LastInvoice = 0;
            $this->code = $this->document_type ."-0";
            $this->label = $this->document_type ."-0";
        }
        // else we use is from db
        else{
            $this->LastInvoice = $this->LastInvoice->id;
            $this->code = $this->document_type ."-". $this->LastInvoice;
            $this->label = $this->document_type ."-". $this->LastInvoice;
        }

        $this->CompaniesSelect = Companies::select('id', 'code','client_type','civility','label','last_name')->where('statu_supplier', '=', 2)->orderBy('code')->get();
    }

    
    public function render()
    {
        $userSelect = User::select('id', 'name')->get();
        //Select task where statu is open and only purchase type
        $PurchasesWaintingInvoiceLineslist = $this->PurchasesWaintingInvoiceLineslist = PurchaseReceiptLines::orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                                                                                        ->whereHas('purchaseReceipt', function($q){
                                                                                            $q->where('companies_id','like', '%'.$this->companies_id.'%');
                                                                                            $q->where('statu',1);
                                                                                        })
                                                                                        ->get();

        return view('livewire.purchases-wainting-invoice', [
            'PurchasesWaintingInvoiceLineslist' => $PurchasesWaintingInvoiceLineslist,
            'userSelect' => $userSelect,
        ]);
    }

    public function storeInvoice(){
        //check rules
        $this->validate(); 

         //check if line exist
        $i = 0;
        foreach ($this->data as $key => $item) {
            if(array_key_exists("purchase_receipt_line_id",$this->data[$key])){
                if($this->data[$key]['purchase_receipt_line_id'] != false ){
                    $i++;
                }
            }
        }
        

        if($i>0){

            // Create puchase order
            $InvoiceCreated = PurchaseInvoice::create([
                'code'=>$this->code,  
                'label'=>$this->label, 
                'companies_id'=>$this->companies_id,
                'user_id'=>$this->user_id,
            ]);

            // Create lines
            foreach ($this->data as $key => $item) {
                //check if add line to new receipt line is aviable
                $PurchaseReceiptLines = PurchaseReceiptLines::find($key);
                
                // Create invoice line
                $PurchaseInvoiceLines = PurchaseInvoiceLines::create([
                    'purchase_invoice_id' => $InvoiceCreated->id, 
                    'purchase_receipt_line_id' => $PurchaseReceiptLines->id, 
                    'purchase_line_id' => $PurchaseReceiptLines->purchase_line_id, 
                ]); 
                /* // update statu line of purchase order line*/
                PurchaseLines::where('id',$PurchaseInvoiceLines->purchase_line_id)->update(['invoiced_qty'=>$PurchaseReceiptLines->receipt_qty]);
            }

            return redirect()->route('purchase.invoices.show', ['id' => $InvoiceCreated->id])->with('success', 'Successfully created new purchase invoice');
        }
        else{
            $errors = $this->getErrorBag();
            $errors->add('errors', 'no lines selected');
        }
    }
}
