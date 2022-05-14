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
    public $sortField = 'ordre'; // default sorting field
    public $sortAsc = true; // default sort direction
    
    public $OrderId;
    public $OrderStatu;
    public $status_id;

    public $OrderLineslist;
    public $order_lines_id, $orders_id, $ordre, $product_id, $qty, $methods_units_id, $selling_price, $accounting_vats_id, $delivery_date, $statu;
    public $code='';
    public $label='';
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
        'ordre'=>'required',
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

    
    public function mount($OrderId, $OrderStatu, $OrderDelay) 
    {
        $this->orders_id = $OrderId;
        $this->order_Statu = $OrderStatu;
        $this->delivery_date = $OrderDelay;
        $this->status_id = Status::select('id')->orderBy('order')->first();
        $this->ProductsSelect = Products::select('id', 'label', 'code')->orderBy('code')->get();
        $this->VATSelect = AccountingVat::select('id', 'label')->orderBy('rate')->get();
        $this->UnitsSelect = MethodsUnits::select('id', 'label', 'code')->orderBy('label')->get();
        $this->Factory = Factory::first();
        $this->ProductSelect = Products::select('id', 'code','label', 'methods_services_id')->get();
        $this->TechServicesSelect = MethodsServices::select('id', 'code','label', 'type')->where('type', '=', 1)->orWhere('type', '=', 7)->orderBy('ordre')->get();
        $this->BOMServicesSelect = MethodsServices::select('id', 'code','label', 'type')->where('type', '=', 2)
                                                                            ->orWhere('type', '=', 3)
                                                                            ->orWhere('type', '=', 4)
                                                                            ->orWhere('type', '=', 5)
                                                                            ->orWhere('type', '=', 6)
                                                                            ->orWhere('type', '=', 8)
                                                                            ->orderBy('ordre')->get();
}

    public function render()
    {
        $OrderLineslist = $this->OrderLineslist = Orderlines::orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')->where('orders_id', '=', $this->orders_id)->where('label','like', '%'.$this->search.'%')->get();
        return view('livewire.order-lines', [
            'OrderLineslist' => $OrderLineslist,
        ]);
    }

    public function resetFields(){
        $this->ordre = $this->ordre+1;
        $this->code = '';
        $this->product_id = '';
        $this->label = '';
    }

    public function storeOrderLine(){

        $this->validate();
        // Create Line
        Orderlines::create([
            'orders_id'=>$this->orders_id,
            'ordre'=>$this->ordre,
            'code'=>$this->code,
            'product_id'=>$this->product_id,
            'label'=>$this->label,
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
        $this->ordre = $Line->ordre;
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

    public function duplicateLine($id){
        $Orderline = Orderlines::findOrFail($id);
        $newOrderline = $Orderline->replicate();
        $newOrderline->ordre = $Orderline->ordre+1;
        $newOrderline->code = $Orderline->code ."#duplicate". $Orderline->id;
        $newOrderline->label = $Orderline->label ."#duplicate". $Orderline->id;
        $newOrderline->save();

        $Tasks = Task::where('order_lines_id', $id)->get();
        foreach ($Tasks as $Task) 
        {
            $newTask = $Task->replicate();
            $newTask->order_lines_id = $newOrderline->id;
            $newTask->save();
        }
    }

    public function cancel()
    {
        $this->updateLines = false;
        $this->resetFields();
    }

    public function up($idStatu){
        // Update line
        Orderlines::find($idStatu)->increment('ordre',1);;
        session()->flash('success','Line Updated Successfully');
    }

    public function down($idStatu){
        // Update line
        Orderlines::find($idStatu)->decrement('ordre',1);;
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
            $OderLineToUpdate->ordre = $this->ordre;
            $OderLineToUpdate->code = $this->code;
            $OderLineToUpdate->product_id = $this->product_id;
            $OderLineToUpdate->label = $this->label;
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