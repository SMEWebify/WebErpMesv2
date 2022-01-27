<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Admin\Factory;
use App\Models\Planning\Task;
use App\Models\Planning\Status;
use App\Models\Products\Products;
use App\Models\Workflow\Quotelines;
use App\Models\Methods\MethodsUnits;
use App\Models\Methods\MethodsServices;
use App\Models\Accounting\AccountingVat;

class QuoteLine extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $sortField = 'ORDRE'; // default sorting field
    public $sortAsc = true; // default sort direction
    
    public $QuoteId;
    public $QuoteStatu;
    public $status_id;

    public $QuoteLineslist;
    public $quote_lines_id, $quotes_id, $ORDRE, $code, $product_id, $qty, $methods_units_id, $selling_price, $accounting_vats_id, $delivery_date, $statu;
    public $label='';
    public $discount= 0;
    public $updateLines = false;
    public $ProductsSelect = [];
    public $UnitsSelect = [];
    public $VATSelect = [];
    public $Factory = [];
    public $ProductSelect  = [];
    public $TechServicesSelect = [];
    public $BOMServicesSelect = [];
    public $TechProductList = [];
    public $BOMProductList = [];

    // Validation Rules
    protected $rules = [
        'ORDRE'=>'required',
        'label'=>'required',
        'qty'=>'required',
        'methods_units_id'=>'required',
        'selling_price'=>'required',
        'discount'=>'required',
        'accounting_vats_id'=>'required',
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

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function mount($QuoteId, $QuoteStatu, $QuoteDelay) 
    {
        $this->quotes_id = $QuoteId;
        $this->quote_Statu = $QuoteStatu;
        $this->delivery_date = $QuoteDelay;
        $this->status_id = Status::select('id')->orderBy('order')->first();
        $this->ProductsSelect = Products::select('id', 'label', 'code')->orderBy('code')->get();
        $this->VATSelect = AccountingVat::select('id', 'label')->orderBy('rate')->get();
        $this->UnitsSelect = MethodsUnits::select('id', 'label', 'code')->orderBy('label')->get();
        $this->Factory = Factory::first();
        $this->ProductSelect = Products::select('id', 'code','label', 'methods_services_id')->get();
        $this->TechServicesSelect = MethodsServices::select('id', 'code','label', 'type')->where('type', '=', 1)->orWhere('type', '=', 7)->orderBy('ORDRE')->get();
        $this->BOMServicesSelect = MethodsServices::select('id', 'code','label', 'type')->where('type', '=', 2)
                                                                            ->orWhere('type', '=', 3)
                                                                            ->orWhere('type', '=', 4)
                                                                            ->orWhere('type', '=', 5)
                                                                            ->orWhere('type', '=', 6)
                                                                            ->orWhere('type', '=', 8)
                                                                            ->orderBy('ORDRE')->get();
    }

    public function render()
    {
        $QuoteLineslist = $this->QuoteLineslist = Quotelines::orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')->where('quotes_id', '=', $this->quotes_id)->where('label','like', '%'.$this->search.'%')->get();
        
        return view('livewire.quote-lines', [
            'QuoteLineslist' => $QuoteLineslist,
        ]);
    }

    public function resetFields(){
        $this->ORDRE = $this->ORDRE+1;
        $this->code = '';
        $this->product_id = '';
        $this->label = '';
    }

    public function storeQuoteLine(){
        $this->validate();
        // Create Line
        Quotelines::create([
            'quotes_id'=>$this->quotes_id,
            'ORDRE'=>$this->ORDRE,
            'code'=>$this->code,
            'product_id'=>$this->product_id,
            'label'=>$this->label,
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

    public function editQuoteLine($id){
        $Line = Quotelines::findOrFail($id);
        $this->quote_lines_id = $id;
        $this->ORDRE = $Line->ORDRE;
        $this->code = $Line->code;
        $this->product_id = $Line->product_id;
        $this->label = $Line->label;
        $this->qty = $Line->qty;
        $this->methods_units_id = $Line->methods_units_id;
        $this->selling_price = $Line->selling_price;
        $this->discount = $Line->discount;
        $this->accounting_vats_id = $Line->accounting_vats_id;
        $this->delivery_date = $Line->delivery_date;     
        $this->statu = $Line->statu;
        $this->updateLines = true;
    }

    public function updateQuoteLine(){
        // Validate request
        $this->validate();
        // Update line
        Quotelines::find($this->quote_lines_id)->fill([
            'ORDRE'=>$this->ORDRE,
            'code'=>$this->code,
            'product_id'=>$this->product_id,
            'label'=>$this->label,
            'qty'=>$this->qty,
            'methods_units_id'=>$this->methods_units_id,
            'selling_price'=>$this->selling_price,
            'discount'=>$this->discount,
            'accounting_vats_id'=>$this->accounting_vats_id,
            'delivery_date'=>$this->delivery_date,
            'statu'=>$this->statu,
        ])->save();
        session()->flash('success','Line Updated Successfully');
    }

    public function cancel()
    {
        $this->updateLines = false;
        $this->resetFields();
    }

    public function upQuoteLine($idStatu){
        // Update line
        Quotelines::find($idStatu)->increment('ORDRE',1);;
        session()->flash('success','Line Updated Successfully');
    }

    public function downQuoteLine($idStatu){
        // Update line
        Quotelines::find($idStatu)->decrement('ORDRE',1);;
        session()->flash('success','Line Updated Successfully');
    }

    public function destroyQuoteLine($id){
        try{
            Quotelines::find($id)->delete();
            Task::where('quote_lines_id',$id)->delete();
            session()->flash('success',"Line deleted Successfully!!");
        }catch(\Exception $e){
            session()->flash('error',"Something goes wrong while deleting Line");
        }
    }
}
