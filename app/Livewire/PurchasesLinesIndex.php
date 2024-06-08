<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Admin\Factory;
use App\Models\Planning\Status;
use App\Models\Products\Products;
use App\Models\Methods\MethodsUnits;
use App\Models\Purchases\PurchaseLines;
use App\Models\Accounting\AccountingVat;

class PurchasesLinesIndex extends Component
{

    use WithPagination;
    protected $paginationTheme = 'bootstrap';
    public $updateLines = false;
    public $search = '';
    public $sortField = 'label'; // default sorting field
    public $sortAsc = true; // default sort direction

    public $purchase_id;
    public $search_product_id;
    
    public $OrderStatu;
    public $order_Statu;
    public $OrderType;
    public $status_id;
    public $OrderLineslist;
    public $order_lines_id, $orders_id, $ordre,$product_id, $methods_units_id, $selling_price, $accounting_vats_id, $delivery_date, $statu;
    public $code='';
    public $label='';
    public $qty= 0;
    public $discount= 0;
    public $ProductsSelect = [];
    public $UnitsSelect = [];
    public $VATSelect = [];
    public $Factory = [];
    public $ProductSelect  = [];
    public $TechServicesSelect = [];
    public $BOMServicesSelect = [];
    public $TechProductList = [];
    public $BOMProductList = [];

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

    public function mount($purchase_id, $OrderStatu) 
    {
        $this->orders_id = $purchase_id;
        $this->order_Statu = $OrderStatu;
        $this->Factory = Factory::first();
        $this->status_id = Status::select('id')->orderBy('order')->first();
        $this->ProductsSelect = Products::select('id', 'label', 'code')->orderBy('code')->get();
        $this->VATSelect = AccountingVat::select('id', 'label')->orderBy('rate')->get();
        $this->UnitsSelect = MethodsUnits::select('id', 'label', 'code')->orderBy('label')->get();
        $this->ProductSelect = Products::select('id', 'code','label', 'methods_services_id')->get();
}

    public function render()
    {
        if(is_numeric($this->search_product_id)){
            $PurchaseLines = PurchaseLines::where('product_id', $this->search_product_id)
                                    ->where('label','like', '%'.$this->search.'%')
                                    ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                                    ->get();
        
        }
        else{
            $PurchaseLines = PurchaseLines::where('purchases_id', $this->purchase_id)
                                            ->where('label','like', '%'.$this->search.'%')
                                            ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')->get();
        }
        
        return view('livewire.purchases-lines-index',[
            'PurchasesLineslist' => $PurchaseLines,
        ]);
    }

    public function storeOrderLine(){

        $this->validate([
            'product_id' => 'required',
            'ordre' =>'required|numeric|gt:0',
            'label'=>'required',
            'qty'=>'required|min:1|not_in:0',
            'selling_price'=>'required|numeric|gt:0',
            'discount'=>'required',
            'methods_units_id'=>'required',
            'accounting_vats_id'=>'required',
        ]);
        
        // Create Line
        $NewPurchaseLines = PurchaseLines::create([
            'purchases_id'=>$this->orders_id,
            'ordre'=>$this->ordre,
            'code'=>$this->code,
            'product_id'=>$this->product_id,
            'label'=>$this->label,
            'qty'=>$this->qty,
            'selling_price'=>$this->selling_price,
            'discount'=>$this->discount,
            'methods_units_id'=>$this->methods_units_id,
            'accounting_vats_id'=>$this->accounting_vats_id,
        ]);

        // Set Flash Message
        session()->flash('success','Line added Successfully');
        // Reset Form Fields After Creating line
        $this->resetFields();
    }

    public function ChangeCodelabel()
    {
        $product = Products::select('id', 'label', 'code', 'methods_units_id', 'selling_price')->where('id', $this->product_id)->get();
        if(count($product) > 0){
            $this->code = $product[0]->code ;
            $this->label =  $product[0]->label;
            $this->methods_units_id =  $product[0]->methods_units_id;
            $this->selling_price =  $product[0]->selling_price;
        }else{
            $this->code ='';
            $this->label ='';
            $this->methods_units_id ='';
            $this->selling_price ='';
        }
    }

    public function resetFields(){
        $this->ordre = $this->ordre+1;
        $this->code = '';
        $this->product_id = '';
        $this->label = '';
    }
}
