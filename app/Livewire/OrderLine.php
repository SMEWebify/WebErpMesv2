<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Admin\Factory;
use App\Models\Planning\Task;
use App\Models\Planning\Status;
use App\Models\Products\Products;
use App\Models\Workflow\OrderLines;
use App\Models\Methods\MethodsUnits;
use App\Models\Planning\SubAssembly;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsServices;
use App\Models\Accounting\AccountingVat;
use App\Models\Workflow\OrderLineDetails;

class OrderLine extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $sortField = 'ordre'; // default sorting field
    public $sortAsc = true; // default sort direction
    
    public $OrderId;
    public $OrderStatu;
    public $order_Statu;
    public $status_id;

    public $OrderLineslist;
    public $order_lines_id, $orders_id, $ordre, $product_id, $methods_units_id, $selling_price, $accounting_vats_id, $delivery_date, $statu;
    public $code='';
    public $label='';
    public $qty= 0;
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
        'ordre' =>'required|numeric|gt:0',
        'label'=>'required',
        'qty'=>'required',
        'methods_units_id'=>'required',
        'selling_price'=>'required|numeric|gt:0',
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
        $date = date_create($this->delivery_date);
        $internalDelay = date_format(date_sub($date , date_interval_create_from_date_string($this->Factory->add_delivery_delay_order. " days")), 'Y-m-d');
        
        // Create Line
        $NewOrderLine = Orderlines::create([
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
            'internal_delay'=>$internalDelay,
            'delivery_date'=>$this->delivery_date,
        ]);

        //add line detail
        OrderLineDetails::create(['order_lines_id'=>$NewOrderLine->id]);

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

        $OrderLineDetails = OrderLineDetails::where('order_lines_id', $id)->first();
        $newOrderLineDetails = $OrderLineDetails->replicate();
        $newOrderLineDetails->order_lines_id = $newOrderline->id;
        $newOrderLineDetails->save();

        $Tasks = Task::where('order_lines_id', $id)->get();
        foreach ($Tasks as $Task) 
        {
            $newTask = $Task->replicate();
            $newTask->order_lines_id = $newOrderline->id;
            $newTask->save();
        }
        
        $SubAssemblyLine = SubAssembly::where('order_lines_id', $id)->get();
        foreach ($SubAssemblyLine as $SubAssembly) 
        {
            $newSubAssembly = $SubAssembly->replicate();
            $newSubAssembly->order_lines_id = $newOrderline->id;
            $newSubAssembly->save();
        }
    }

    public function CreatProduct($id){

        $ServiceComponent = MethodsServices::where('type', 8)->first();
        $FamilieComponent = MethodsFamilies::where('service_id', $ServiceComponent->id)->first();

        if(!empty($ServiceComponent->id) && !empty($FamilieComponent->id)){
            //get data to dulicate for new order
            $OrderlineData = Orderlines::find($id);
            $newProduct = Products::create([
                'code'=>$OrderlineData->code,
                'label'=>$OrderlineData->label,
                'methods_services_id'=> $ServiceComponent->id,
                'methods_families_id'=> $FamilieComponent->id,
                'purchased'=> 2,
                'purchased_price'=> 1,
                'sold'=> 1,
                'selling_price'=> $OrderlineData->selling_price,
                'methods_units_id'=>$OrderlineData->methods_units_id,
                'tracability_type'=>1,
                
            ]);
            //update info that order line as task
            $OrderlineData->product_id = $newProduct->id;
            $OrderlineData->save();

            //add line detail
            $OrderLineDetailData = OrderLineDetails::where('order_lines_id', $id)->first();
            $newProductDetail = Products::findOrFail($newProduct->id);
            $newProductDetail->material = $OrderLineDetailData->material;
            $newProductDetail->thickness = $OrderLineDetailData->thickness;
            $newProductDetail->weight = $OrderLineDetailData->weight;
            $newProductDetail->x_size = $OrderLineDetailData->x_size;
            $newProductDetail->y_size = $OrderLineDetailData->y_size;
            $newProductDetail->z_size = $OrderLineDetailData->z_size;
            $newProductDetail->x_oversize = $OrderLineDetailData->x_oversize;
            $newProductDetail->y_oversize = $OrderLineDetailData->y_oversize;
            $newProductDetail->z_oversize = $OrderLineDetailData->z_oversize;
            $newProductDetail->diameter = $OrderLineDetailData->diameter;
            $newProductDetail->diameter_oversize = $OrderLineDetailData->diameter_oversize;
            $newProductDetail->save();

            $Tasks = Task::where('order_lines_id', $id)->get();
            foreach ($Tasks as $Task) 
            {
                $newTask = $Task->replicate();
                $newTask->products_id = $newProduct->id;
                $newTask->order_lines_id = null;
                $newTask->save();
            }
            
            $SubAssemblyLine = SubAssembly::where('order_lines_id', $id)->get();
            foreach ($SubAssemblyLine as $SubAssembly) 
            {
                $newSubAssembly = $SubAssembly->replicate();
                $newSubAssembly->products_id = $newProduct->id;
                $newSubAssembly->order_lines_id = null;
                $newSubAssembly->save();
            }
            
            session()->flash('success','Product created Successfully');
        }
        else{
            session()->flash('error','No component service or family');
        }
    }

    public function breakDown($id){
        $OrderLine = OrderLines::findOrFail($id);
        $TaskLine = Task::where('products_id', $OrderLine->product_id)->get();
        foreach ($TaskLine as $Task) 
        {
            $newTask = $Task->replicate();
            $newTask->order_lines_id = $id;
            $newTask->products_id = null;
            $newTask->save();
        }
        $SubAssemblyLine = SubAssembly::where('products_id', $OrderLine->product_id)->get();
        foreach ($SubAssemblyLine as $SubAssembly) 
        {
            $newSubAssembly = $SubAssembly->replicate();
            $newSubAssembly->order_lines_id = $id;
            $newSubAssembly->products_id = null;
            $newSubAssembly->save();
        }

        $OrderLine->tasks_status = 2;
        $OrderLine->save();
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
            $date = date_create($this->delivery_date);
            $internalDelay = date_format(date_sub($date , date_interval_create_from_date_string($this->Factory->add_delivery_delay_order. " days")), 'Y-m-d');
        
            
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
            $OderLineToUpdate->internal_delay = $internalDelay;
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