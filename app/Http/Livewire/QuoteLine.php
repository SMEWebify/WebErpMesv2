<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Products\Products;
use App\Models\Methods\MethodsUnits;
use App\Models\Accounting\AccountingVat;
use App\Models\workflow\Quotelines As QuoteLineslist;

class QuoteLine extends Component
{
    public $QuoteId;

    public $QuoteLineslist;
    public $quotes_id, $ORDRE, $CODE, $product_id, $LABEL, $qty, $methods_units_id, $selling_price, $discount, $accounting_vats_id, $delivery_date, $statu;
    public $updateLines = false;


    public $ProductsSelect = [];
    public $UnitsSelect = [];
    public $VATSelect = [];
    


    protected $listeners = [
        'deleteCategory'=>'destroy'
    ];

    // Validation Rules
    protected $rules = [
        'CODE' =>'required',
        'ORDRE'=>'required',
        'qty'=>'required',
        'methods_units_id'=>'required',
        'selling_price'=>'required',
        'accounting_vats_id'=>'required',
    ];

    public function mount() 
    {
        $this->quotes_id = "1";
        $this->ProductsSelect = Products::select('id', 'LABEL', 'CODE')->orderBy('CODE')->get();
        $this->VATSelect = AccountingVat::select('id', 'LABEL')->orderBy('LABEL')->get();
        $this->UnitsSelect = MethodsUnits::select('id', 'LABEL', 'CODE')->orderBy('LABEL')->get();
        
    }

    public function render()
    {
        $QuoteId = $this->QuoteId;

        //$this->QuoteLineslist = QuoteLineslist::orderBy('ORDRE')->where('quotes_id', '=', $QuoteId);
        $this->QuoteLineslist = QuoteLineslist::all();
        return view('livewire.quote-lines');
    }

    public function resetFields(){
        $this->ORDRE = $this->ORDRE+10;
        $this->CODE = '';
        $this->product_id = '';
        $this->LABEL = '';
        $this->qty = '';
        $this->selling_price = '';
    }

    public function storeQuoteLine(){

        $this->validate();

            // Create Category
            QuoteLineslist::create([
                'quotes_id'=>$this->quotes_id,
                'ORDRE'=>$this->ORDRE,
                'CODE'=>$this->CODE,
                'product_id'=>$this->product_id,
                'LABEL'=>$this->LABEL,
                'qty'=>$this->qty,
                'methods_units_id'=>$this->methods_units_id,
                'selling_price'=>$this->selling_price,
                'discount'=>$this->discount,
                'accounting_vats_id'=>$this->accounting_vats_id,
                'delivery_date'=>$this->delivery_date,
            ]);
    
            // Set Flash Message
            session()->flash('success','Line added Successfully');

            // Reset Form Fields After Creating line
            $this->resetFields();
       
    }

    public function edit($id){
        $Line = QuoteLineslist::findOrFail($id);
        $this->ORDRE = $Line->ORDRE;
        $this->CODE = $Line->CODE;
        $this->product_id = $Line->product_id;
        $this->LABEL = $Line->LABEL;
        $this->qty = $Line->qty;
        $this->methods_units_id = $Line->methods_units_id;
        $this->selling_price = $Line->selling_price;
        $this->discount = $Line->discount;
        $this->accounting_vats_id = $Line->accounting_vats_id;
        $this->delivery_date = $Line->delivery_date;     
        $this->statu = $Line->statu;
        $this->updateLines = true;
    }

    public function cancel()
    {
        $this->updateLines = false;
        $this->resetFields();
    }

    public function update(){

        // Validate request
        $this->validate();

        try{

            // Update line
            QuoteLineslist::find($this->category_id)->fill([
                'ORDRE'=>$this->ORDRE,
                'CODE'=>$this->CODE,
                'product_id'=>$this->product_id,
                'LABEL'=>$this->LABEL,
                'qty'=>$this->qty,
                'methods_units_id'=>$this->methods_units_id,
                'selling_price'=>$this->selling_price,
                'discount'=>$this->discount,
                'accounting_vats_id'=>$this->accounting_vats_id,
                'delivery_date'=>$this->delivery_date,
                'statu'=>$this->statu,
            ])->save();

            session()->flash('success','Line Updated Successfully');
    
            $this->cancel();
        }catch(\Exception $e){

            session()->flash('error','Something goes wrong while updating line');
            $this->cancel();
        }
    }

    public function destroy($id){
        try{
            QuoteLineslist::find($id)->delete();
            session()->flash('success',"Line deleted Successfully!!");
        }catch(\Exception $e){
            session()->flash('error',"Something goes wrong while deleting Line");
        }
    }
}
