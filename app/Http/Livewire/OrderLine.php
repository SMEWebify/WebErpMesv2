<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Admin\Factory;
use App\Models\Planning\Task;
use App\Models\Products\Products;
use App\Models\Workflow\Orderlines;
use App\Models\Methods\MethodsUnits;
use App\Models\Methods\MethodsServices;
use App\Models\Accounting\AccountingVat;
use App\Models\Planning\Status;

class OrderLine extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $sortField = 'ORDRE'; // default sorting field
    public $sortAsc = true; // default sort direction
    
    public $OrderId;
    public $OrderStatu;
    public $status_id;

    public $OrderLineslist;
    public $order_lines_id, $orders_id, $ORDRE, $CODE, $product_id, $qty, $methods_units_id, $selling_price, $accounting_vats_id, $delivery_date, $statu;
    public $LABEL='';
    public $discount= 0;
    public $updateLines = false;
    public $ProductsSelect = [];
    public $UnitsSelect = [];
    public $VATSelect = [];
    public $ProductSelect  = [];
    public $TechServicesSelect = [];
    public $BOMServicesSelect = [];
    public $TechProductList = [];
    public $BOMProductList = [];

    // Validation Rules
    protected $rules = [
        'ORDRE'=>'required',
        'LABEL'=>'required',
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

    public function mount($OrderId, $OrderStatu, $OrderDelay) 
    {
        $this->orders_id = $OrderId;
        $this->order_Statu = $OrderStatu;
        $this->delivery_date = $OrderDelay;
        $this->status_id = Status::select('id')->orderBy('order')->first();
        $this->ProductsSelect = Products::select('id', 'LABEL', 'CODE')->orderBy('CODE')->get();
        $this->VATSelect = AccountingVat::select('id', 'LABEL')->orderBy('RATE')->get();
        $this->UnitsSelect = MethodsUnits::select('id', 'LABEL', 'CODE')->orderBy('LABEL')->get();
        $this->Factory = Factory::first();
        $this->ProductSelect = Products::select('id', 'CODE','LABEL', 'methods_services_id')->get();
        $this->TechServicesSelect = MethodsServices::select('id', 'CODE','LABEL', 'TYPE')->where('TYPE', '=', 1)->orWhere('TYPE', '=', 7)->orderBy('ORDRE')->get();
        $this->BOMServicesSelect = MethodsServices::select('id', 'CODE','LABEL', 'TYPE')->where('TYPE', '=', 2)
                                                                            ->orWhere('TYPE', '=', 3)
                                                                            ->orWhere('TYPE', '=', 4)
                                                                            ->orWhere('TYPE', '=', 5)
                                                                            ->orWhere('TYPE', '=', 6)
                                                                            ->orWhere('TYPE', '=', 8)
                                                                            ->orderBy('ORDRE')->get();
    }

    public function render()
    {
        $OrderLineslist = $this->OrderLineslist = Orderlines::orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')->where('orders_id', '=', $this->orders_id)->where('LABEL','like', '%'.$this->search.'%')->get();
        
        return view('livewire.order-lines', [
            'OrderLineslist' => $OrderLineslist,
        ]);
    }

    public function resetFields(){
        $this->ORDRE = $this->ORDRE+1;
        $this->CODE = '';
        $this->product_id = '';
        $this->LABEL = '';
    }

    public function storeOrderLine(){

        $this->validate();
        // Create Line
        Orderlines::create([
            'orders_id'=>$this->orders_id,
            'ORDRE'=>$this->ORDRE,
            'CODE'=>$this->CODE,
            'product_id'=>$this->product_id,
            'LABEL'=>$this->LABEL,
            'qty'=>$this->qty,
            'delivered_remaining_qty'=>$this->qty,
            'invoiced_remaining_qty'=>$this->qty,
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
        $Line = Orderlines::findOrFail($id);
        $this->order_lines_id = $id;
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

    public function up($idStatu){
        // Update line
        Orderlines::find($idStatu)->increment('ORDRE',1);;
        session()->flash('success','Line Updated Successfully');
    }

    public function down($idStatu){
        // Update line
        Orderlines::find($idStatu)->decrement('ORDRE',1);;
        session()->flash('success','Line Updated Successfully');
    }

    public function update(){
        // Validate request
        $this->validate();
        // Update line
        $OderLineToUpdate = Orderlines::find($this->order_lines_id);

        //if we have already part delivered and new qty no match whit old qty
        if($OderLineToUpdate->delivered_qty > $this->qty && $OderLineToUpdate->delivered_qty != 0){
            session()->flash('error','Cant update if delivered remaining qty is superior to new quantity');
        }
        else{
            //if new qty change statu because were have new part produce
            // in future update task statu if they are finihed ?
            if( $OderLineToUpdate->delivery_status > 1 && $OderLineToUpdate->qty != $this->qty && $OderLineToUpdate->delivered_qty != 0 ){
                $OderLineToUpdate->delivery_status = 2;
            }
            /*
            this is avaible only if we check if delevery not exist
            if($OderLineToUpdate->delivered_qty == $this->qty ){
                $OderLineToUpdate->delivery_status = 3;
            }*/
            $OderLineToUpdate->ORDRE = $this->ORDRE;
            $OderLineToUpdate->CODE = $this->CODE;
            $OderLineToUpdate->product_id = $this->product_id;
            $OderLineToUpdate->LABEL = $this->LABEL;
            $OderLineToUpdate->qty = $this->qty;
            $OderLineToUpdate->delivered_remaining_qty = $this->qty;
            $OderLineToUpdate->invoiced_remaining_qty = $this->qty;
            $OderLineToUpdate->methods_units_id = $this->methods_units_id;
            $OderLineToUpdate->selling_price = $this->selling_price;
            $OderLineToUpdate->discount = $this->discount;
            $OderLineToUpdate->accounting_vats_id = $this->accounting_vats_id;
            $OderLineToUpdate->delivery_date = $this->delivery_date;
            $OderLineToUpdate->save();
    
            session()->flash('success','Line Updated Successfully');
        }
    }

    public function destroy($id){
        try{
            Orderlines::find($id)->delete();
            Task::where('order_lines_id',$id)->delete();
            session()->flash('success',"Line deleted Successfully!!");
        }catch(\Exception $e){
            session()->flash('error',"Something goes wrong while deleting Line");
        }
    }
}