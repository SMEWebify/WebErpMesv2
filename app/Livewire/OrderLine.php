<?php

namespace App\Livewire;

use Carbon\Carbon;
use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use App\Models\Admin\Factory;
use App\Models\Planning\Task;
use App\Models\Planning\Status;
use App\Models\Workflow\Orders;
use App\Events\OrderLineUpdated;
use App\Models\Products\Products;
use App\Models\Workflow\Invoices;
use App\Models\Products\StockMove;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\OrderLines;
use App\Models\Methods\MethodsUnits;
use App\Models\Planning\SubAssembly;
use Illuminate\Support\Facades\Auth;
use App\Models\Workflow\InvoiceLines;
use App\Models\Products\SerialNumbers;
use App\Models\Workflow\DeliveryLines;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsServices;
use App\Models\Accounting\AccountingVat;
use App\Models\Workflow\OrderLineDetails;
use App\Models\Quality\QualityNonConformity;
use Illuminate\Support\Facades\Notification;
use App\Models\Products\StockLocationProducts;
use App\Notifications\NonConformityNotification;

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
    public $OrderType;
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

    public $data = [];
    public $RemoveFromStock = false;
    public $CreateSerialNumber = false;
    
    private $deleveryOrdre = 10;
    private $invoiceOrdre = 10;

    // Validation Rules
    protected $rules = [
        'ordre' =>'required|numeric|gt:0',
        'label'=>'required',
        'qty'=>'required|min:1|not_in:0',
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

    
    public function mount($OrderId, $OrderStatu, $OrderDelay, $OrderType) 
    {
        $this->orders_id = $OrderId;
        $this->order_Statu = $OrderStatu;
        $this->delivery_date = $OrderDelay;
        $this->OrderType = $OrderType;
        $this->Factory = Factory::first();
        $this->status_id = Status::select('id')->orderBy('order')->first();
        $this->ProductsSelect = Products::select('id', 'label', 'code')->orderBy('code')->get();
        $this->VATSelect = AccountingVat::select('id', 'label')->orderBy('rate')->get();
        $this->UnitsSelect = MethodsUnits::select('id', 'label', 'code')->orderBy('label')->get();
        $this->ProductSelect = Products::select('id', 'code','label', 'methods_services_id')->get();
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
        
        if($this->OrderType == 2){
            $this->validate([
                'product_id' => 'required',
                'ordre' =>'required|numeric|gt:0',
                'label'=>'required',
                'qty'=>'required|min:1|not_in:0',
                'methods_units_id'=>'required',
                'selling_price'=>'required|numeric|gt:0',
                'discount'=>'required',
                'accounting_vats_id'=>'required',
            ]);
        }
        else{
            $this->validate();
        }

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
        if(!empty($ServiceComponent->id)){
            
            $FamilieComponent = MethodsFamilies::where('methods_services_id', $ServiceComponent->id)->first();

            if(!empty($FamilieComponent->id)){
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
                $newProductDetail->finishing = $OrderLineDetailData->finishing;
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
                session()->flash('error','No component familly');
            }
        }
        else{
            session()->flash('error','No component service');
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
        if($this->OrderType == 2){
            $this->validate([
                'product_id' => 'required',
                'ordre' =>'required|numeric|gt:0',
                'label'=>'required',
                'qty'=>'required|min:1|not_in:0',
                'methods_units_id'=>'required',
                'selling_price'=>'required|numeric|gt:0',
                'discount'=>'required',
                'accounting_vats_id'=>'required',
            ]);
        }
        else{
            $this->validate();
        }
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

    public function createNC($id, $companie_id){
        $NewNonConformity = QualityNonConformity::create([
            'code'=> "NC-OR-#". $this->orders_id,
            'label'=>"NC-L-#". $id,
            'statu'=>1,
            'type'=>1,
            'user_id'=>Auth::id(),
            'companie_id'=>$companie_id,
            'order_lines_id'=>$id,
        ]);
        $users = User::where('non_conformity_notification', 1)->get();
        Notification::send($users, new NonConformityNotification($NewNonConformity));
        return redirect()->route('quality.nonConformitie')->with('success', 'Successfully created non conformitie.');
    }

    
    public function storeDelevery($orderId){
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

            //get data to dulicate for new order
            $OrderData = Orders::find($orderId);

             //get last Delivery id for create new Code id
            $LastDelivery=  Deliverys::orderBy('id', 'desc')->first();
            if($LastDelivery == Null){
                $deliveryCode = "DN-0";
            }
            else{
                $deliveryCode = "DN-". $LastDelivery->id;
            }

             // Create delivery note
            $DeliveryCreated = Deliverys::create([
                'uuid'=> Str::uuid(),
                'code'=>$deliveryCode,  
                'label'=>$deliveryCode,
                'companies_id'=>$OrderData->companies_id,   
                'companies_addresses_id'=>$OrderData->companies_addresses_id,  
                'companies_contacts_id'=>$OrderData->companies_contacts_id,  
                'user_id'=>Auth::id(),  
            ]);

            if($DeliveryCreated){
                // Create delivery note lines
                foreach ($this->data as $key => $item) {

                    //get data to dulicate for new order
                    $OrderLineData = OrderLines::find($key);
                    // Create delivery line
                    $DeliveryLines = DeliveryLines::create([
                        'deliverys_id' => $DeliveryCreated->id,
                        'order_line_id' => $key, 
                        'ordre' => $this->deleveryOrdre,
                        'qty' => $OrderLineData->delivered_remaining_qty,
                        'statu' => 1
                    ]); 

                    if($this->CreateSerialNumber){
                        $productId = null;
                        if($OrderLineData->product_id) {
                            $productId =$OrderLineData->product_id;
                        }
                        // Generate and insert serial numbers for each qt delivered
                        for ($i = 0; $i <$OrderLineData->delivered_remaining_qty; $i++) {
                            SerialNumbers::create([
                                'products_id' => $productId,
                                'order_line_id' => $OrderLineData->id,
                                'serial_number' => Str::uuid(),
                                'status' => 2, // sold
                            ]);
                        }
                    }

                    // update order line info
                    //same function from stock location product controller
                    $OrderLineData->delivered_qty =  $OrderLineData->delivered_qty + $OrderLineData->delivered_remaining_qty;
                    $OrderLineData->delivered_remaining_qty = $OrderLineData->delivered_remaining_qty - $OrderLineData->delivered_remaining_qty;
                    //if we are delivered all part
                    if($OrderLineData->delivered_remaining_qty == 0){
                        $OrderLineData->delivery_status = 3;
                        $OrderLineData->save();
                        // update order statu info
                        // we must be check if all entry are delivered
                        event(new OrderLineUpdated($OrderLineData->id));
                    }
                    else{
                        $OrderLineData->delivery_status = 2;
                        $OrderLineData->save();
                        // update order statu info
                        event(new OrderLineUpdated($OrderLineData->id));
                    }

                    

                    $TaskRelation = $OrderLineData->Task()->get();

                    if($this->RemoveFromStock && $OrderLineData->product_id && $TaskRelation->isEmpty()){
                        $quantityRemaining = $OrderLineData->qty;

                        $StockLocationProduct = StockLocationProducts::where('products_id', $OrderLineData->product_id)->get();
                        foreach ($StockLocationProduct as $stock) {
                            
                            // Calculate the quantity to exit from this stock
                            $quantityToWithdraw = min($stock->getCurrentStockMove(), $quantityRemaining);
                
                            if($quantityToWithdraw != 0){
                                // Create a negative stock movement to record the stock issue
                                $stockMove = StockMove::create(['user_id' => Auth::id(),
                                                                'qty' => $quantityToWithdraw,
                                                                'stock_location_products_id' =>  $stock->id,  
                                                                'order_line_id' =>$OrderLineData->id,
                                                                'typ_move' =>9,
                                                            ]);
                            }
                
                            // Update remaining quantity
                            $quantityRemaining -= $quantityToWithdraw;
                
                            // Exit the loop if the requested quantity has been satisfied
                            if ($quantityRemaining <= 0) {
                                break;
                            }
                        }
                    }

                    

                    $this->deleveryOrdre= $this->deleveryOrdre+10;
                }
                // return view on new document
                return redirect()->route('deliverys.show', ['id' => $DeliveryCreated->id])->with('success', 'Successfully created new delivery note');
            }
            else{
                return redirect()->back()->with('error', 'Something went wrong');
            }
        }
        else{
            $errors = $this->getErrorBag();
            $errors->add('errors', 'no lines selected');
        }
    }

    public function storeInvoice($orderId){
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

            //get data to dulicate for new order
            $OrderData = Orders::find($orderId);

            //get last Invoice id for create new Code id
            $LastInvoice=  Invoices::orderBy('id', 'desc')->first();
            if($LastInvoice == Null){
                $invoiceCode = "IN-0";
            }
            else{
                $invoiceCode = "IN-". $LastInvoice->id;
            }

            // Create invoice
            $InvoiceCreated = Invoices::create([
                'uuid'=> Str::uuid(),
                'code'=>$invoiceCode,  
                'label'=>$invoiceCode, 
                'companies_id'=>$OrderData->companies_id,   
                'companies_addresses_id'=>$OrderData->companies_addresses_id,  
                'companies_contacts_id'=>$OrderData->companies_contacts_id,  
                'user_id'=>Auth::id(),
                'due_date' => Carbon::now()->addDays(30),
            ]);

            if($InvoiceCreated){
                // Create invoice note lines
                foreach ($this->data as $key => $item) {

                    //get data to dulicate for new order
                    $OrderLineData = OrderLines::find($key);
                    // Create invoice line
                    $InvoiceLines = InvoiceLines::create([
                        'invoices_id' => $InvoiceCreated->id,
                        'order_line_id' => $key, 
                        'delivery_line_id' => null, 
                        'ordre' => $this->invoiceOrdre,
                        'qty' => $OrderLineData->invoiced_remaining_qty,
                        'statu' => 1
                    ]); 

                    if($this->CreateSerialNumber){
                        $productId = null;
                        if($OrderLineData->product_id) {
                            $productId =$OrderLineData->product_id;
                        }
                        // Generate and insert serial numbers for each qt delivered
                        for ($i = 0; $i <$OrderLineData->invoiced_remaining_qty; $i++) {
                            SerialNumbers::create([
                                'products_id' => $productId,
                                'order_line_id' => $OrderLineData->id,
                                'serial_number' => Str::uuid(),
                                'status' => 2, // sold
                            ]);
                        }
                    }

                    // update order line info
                    //same function from stock location product controller
                    $OrderLineData->delivered_qty =  $OrderLineData->delivered_qty + $OrderLineData->delivered_remaining_qty;
                    $OrderLineData->invoiced_qty =  $OrderLineData->invoiced_qty + $OrderLineData->invoiced_remaining_qty;
                    $OrderLineData->delivered_remaining_qty = $OrderLineData->delivered_remaining_qty - $OrderLineData->delivered_remaining_qty;
                    $OrderLineData->invoiced_remaining_qty = $OrderLineData->invoiced_remaining_qty - $OrderLineData->invoiced_remaining_qty;
                    //if we are delivered all part
                    if($OrderLineData->delivered_remaining_qty == 0){
                        $OrderLineData->delivery_status = 4;
                        $OrderLineData->invoice_status = 3;
                        $OrderLineData->save();
                        // update order statu info
                        // we must be check if all entry are delivered
                        event(new OrderLineUpdated($OrderLineData->id));
                    }
                    else{
                        $OrderLineData->delivery_status = 2;
                        $OrderLineData->invoice_status = 2;
                        $OrderLineData->save();
                        // update order statu info
                        event(new OrderLineUpdated($OrderLineData->id));
                    }

                    $TaskRelation = $OrderLineData->Task()->get();

                    if($this->RemoveFromStock && $OrderLineData->product_id && $TaskRelation->isEmpty()){
                        $quantityRemaining = $OrderLineData->qty;

                        $StockLocationProduct = StockLocationProducts::where('products_id', $OrderLineData->product_id)->get();
                        foreach ($StockLocationProduct as $stock) {
                            
                            // Calculate the quantity to exit from this stock
                            $quantityToWithdraw = min($stock->getCurrentStockMove(), $quantityRemaining);
                
                            if($quantityToWithdraw != 0){
                                // Create a negative stock movement to record the stock issue
                                $stockMove = StockMove::create(['user_id' => Auth::id(),
                                                                'qty' => $quantityToWithdraw,
                                                                'stock_location_products_id' =>  $stock->id,  
                                                                'order_line_id' =>$OrderLineData->id,
                                                                'typ_move' =>9,
                                                            ]);
                            }
                
                            // Update remaining quantity
                            $quantityRemaining -= $quantityToWithdraw;
                
                            // Exit the loop if the requested quantity has been satisfied
                            if ($quantityRemaining <= 0) {
                                break;
                            }
                        }
                    }


                    $this->invoiceOrdre= $this->invoiceOrdre+10;
                }
                // return view on new document
                return redirect()->route('invoices.show', ['id' => $InvoiceCreated->id])->with('success', 'Successfully created new invoice');
            }
            else{
                return redirect()->back()->with('error', 'Something went wrong');
            }
            
        }
        else{
            $errors = $this->getErrorBag();
            $errors->add('errors', 'no lines selected');
        }
    }
}