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
use Illuminate\Support\Facades\App;
use App\Models\Methods\MethodsUnits;
use App\Models\Planning\SubAssembly;
use App\Services\InvoiceLineService;
use Illuminate\Support\Facades\Auth;
use App\Services\DeliveryLineService;
use App\Models\Products\SerialNumbers;
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
    public $order_lines_id, $orders_id, $ordre = 1, $product_id, $methods_units_id, $selling_price, $accounting_vats_id, $delivery_date, $statu;
    public $code='';
    public $label='';
    public $qty= 0;
    public $discount= 0;
    public $updateLines = false;
    public $ProductsSelect = [];
    public $UnitsSelect = [];
    public $VATSelect = [];
    public $Factory;
    public $ProductSelect  = [];

    public $data = [];
    public $RemoveFromStock = false;
    public $CreateSerialNumber = false;
    
    private $deleveryOrdre = 10;
    private $invoiceOrdre = 10;

    protected $deliveryLineService;
    protected $invoiceLineService;

    public function __construct()
    {
        // Résoudre le service via le container Laravel
        $this->deliveryLineService = App::make(DeliveryLineService::class);
        $this->invoiceLineService = App::make(InvoiceLineService::class);
    }

    // Validation Rules
    protected $rules = [
        'ordre' =>'required|numeric|gt:0',
        'label'=>'required',
        'qty'=>'required|min:1|not_in:0',
        'selling_price'=>'required|numeric|gt:0',
        'discount'=>'required',
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
                'selling_price'=>'required|numeric|gt:0',
                'discount'=>'required',
            ]);

        }
        else{
            $this->validate();
        }

        $AccountingVat = AccountingVat::getDefault(); 
        $MethodsUnits = MethodsUnits::getDefault(); 
        $AccountingVat = ($AccountingVat->id  ?? 0);
        $MethodsUnits = ($MethodsUnits->id  ?? 0);

        if($AccountingVat == 0|| $MethodsUnits == 0 ){
            return redirect()->route('orders.show', ['id' =>  $this->orders_id])->with('error', 'No default settings');
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
            'methods_units_id'=>$MethodsUnits,
            'selling_price'=>$this->selling_price,
            'discount'=>$this->discount,
            'accounting_vats_id'=>$AccountingVat,
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

    public function duplicateLine($id)
    {
        // Duplicate the order line
        $newOrderline = $this->duplicateOrderLine($id);
    
        // Duplicate the order line details
        $this->duplicateOrderLineDetails($id, $newOrderline->id);
    
        // Duplicate the tasks
        $this->duplicateTasks($id, $newOrderline->id);
    
        // Duplicate the sub-assemblies
        $this->duplicateSubAssemblies($id, $newOrderline->id);
    }
    
    private function duplicateOrderLine($id)
    {
        $orderLine = Orderlines::findOrFail($id);
        $newOrderLine = $orderLine->replicate();
        $newOrderLine->ordre = $orderLine->ordre + 1;
        $newOrderLine->code = $orderLine->code . "#duplicate" . $orderLine->id;
        $newOrderLine->label = $orderLine->label . "#duplicate" . $orderLine->id;
        $newOrderLine->save();
    
        return $newOrderLine;
    }
    
    private function duplicateOrderLineDetails($oldOrderLineId, $newOrderLineId)
    {
        $orderLineDetails = OrderLineDetails::where('order_lines_id', $oldOrderLineId)->first();
        $newOrderLineDetails = $orderLineDetails->replicate();
        $newOrderLineDetails->order_lines_id = $newOrderLineId;
        $newOrderLineDetails->save();
    }
    
    private function duplicateTasks($oldOrderLineId, $newOrderLineId)
    {
        $tasks = Task::where('order_lines_id', $oldOrderLineId)->get();
        foreach ($tasks as $task) {
            $newTask = $task->replicate();
            $newTask->order_lines_id = $newOrderLineId;
            $newTask->save();
        }
    }
    
    private function duplicateSubAssemblies($oldOrderLineId, $newOrderLineId)
    {
        $subAssemblies = SubAssembly::where('order_lines_id', $oldOrderLineId)->get();
        foreach ($subAssemblies as $subAssembly) {
            $newSubAssembly = $subAssembly->replicate();
            $newSubAssembly->order_lines_id = $newOrderLineId;
            $newSubAssembly->save();
        }
    }

    public function createProduct($id)
    {
        $serviceComponent = MethodsServices::where('type', 8)->first();
    
        if ($serviceComponent) {
            $familyComponent = MethodsFamilies::where('methods_services_id', $serviceComponent->id)->first();
    
            if ($familyComponent) {
                // Get data to duplicate for new order
                $orderLineData = Orderlines::findOrFail($id);
                $newProduct = $this->createNewProduct($orderLineData, $serviceComponent->id, $familyComponent->id);
    
                // Update info that order line has task
                $orderLineData->product_id = $newProduct->id;
                $orderLineData->save();
    
                // Add line detail
                $this->addProductDetails($newProduct->id, $id);
    
                // Duplicate tasks
                $this->duplicateProductTasks($id, $newProduct->id);
    
                // Duplicate sub-assemblies
                $this->duplicateProductSubAssemblies($id, $newProduct->id);
    
                session()->flash('success', 'Product created successfully');
            } else {
                session()->flash('error', 'No component family');
            }
        } else {
            session()->flash('error', 'No component service');
        }
    }
    
    private function createNewProduct($orderLineData, $serviceComponentId, $familyComponentId)
    {
        return Products::create([
            'code' => $orderLineData->code,
            'label' => $orderLineData->label,
            'methods_services_id' => $serviceComponentId,
            'methods_families_id' => $familyComponentId,
            'purchased' => 2,
            'purchased_price' => 1,
            'sold' => 1,
            'selling_price' => $orderLineData->selling_price,
            'methods_units_id' => $orderLineData->methods_units_id,
            'tracability_type' => 1,
        ]);
    }
    
    private function addProductDetails($newProductId, $orderLineId)
    {
        $orderLineDetailData = OrderLineDetails::where('order_lines_id', $orderLineId)->firstOrFail();
        $newProductDetail = Products::findOrFail($newProductId);
    
        $newProductDetail->material = $orderLineDetailData->material;
        $newProductDetail->thickness = $orderLineDetailData->thickness;
        $newProductDetail->finishing = $orderLineDetailData->finishing;
        $newProductDetail->weight = $orderLineDetailData->weight;
        $newProductDetail->x_size = $orderLineDetailData->x_size;
        $newProductDetail->y_size = $orderLineDetailData->y_size;
        $newProductDetail->z_size = $orderLineDetailData->z_size;
        $newProductDetail->x_oversize = $orderLineDetailData->x_oversize;
        $newProductDetail->y_oversize = $orderLineDetailData->y_oversize;
        $newProductDetail->z_oversize = $orderLineDetailData->z_oversize;
        $newProductDetail->diameter = $orderLineDetailData->diameter;
        $newProductDetail->diameter_oversize = $orderLineDetailData->diameter_oversize;
        $newProductDetail->save();
    }
    
    private function duplicateProductTasks($orderLineId, $newProductId)
    {
        $tasks = Task::where('order_lines_id', $orderLineId)->get();
        foreach ($tasks as $task) {
            $newTask = $task->replicate();
            $newTask->products_id = $newProductId;
            $newTask->order_lines_id = null;
            $newTask->save();
        }
    }
    
    private function duplicateProductSubAssemblies($orderLineId, $newProductId)
    {
        $subAssemblies = SubAssembly::where('order_lines_id', $orderLineId)->get();
        foreach ($subAssemblies as $subAssembly) {
            $newSubAssembly = $subAssembly->replicate();
            $newSubAssembly->products_id = $newProductId;
            $newSubAssembly->order_lines_id = null;
            $newSubAssembly->save();
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
    
    // Helper method to check if lines exist
    private function linesExist()
    {
        $i = 0;
        foreach ($this->data as $key => $item) {
            if (array_key_exists("order_line_id", $this->data[$key]) && $this->data[$key]['order_line_id'] != false) {
                $i++;
            }
        }
        return $i > 0;
    }

    // Helper method to create serial numbers
    private function createSerialNumbers($OrderLineData, $quantity)
    {
        $productId = $OrderLineData->product_id ?? null;
        for ($i = 0; $i < $quantity; $i++) {
            SerialNumbers::create([
                'products_id' => $productId,
                'order_line_id' => $OrderLineData->id,
                'serial_number' => Str::uuid(),
                'status' => 2, // sold
            ]);
        }
    }

    // Helper method to update order line info
    private function updateOrderLineInfoFromDelevery($OrderLineData)
    {
        $OrderLineData->delivered_qty += $OrderLineData->delivered_remaining_qty;
        $OrderLineData->delivered_remaining_qty = 0;
        $OrderLineData->delivery_status = $OrderLineData->delivered_remaining_qty == 0 ? 3 : 2;
        $OrderLineData->save();
        event(new OrderLineUpdated($OrderLineData->id));
    }

    // Helper method to update order line info
    private function updateOrderLineInfoFromInvoice($OrderLineData)
    {
        $OrderLineData->invoiced_qty  += $OrderLineData->invoiced_remaining_qty;
        $OrderLineData->invoiced_remaining_qty = 0;
        $OrderLineData->invoice_status  = $OrderLineData->invoiced_remaining_qty == 0 ? 3 : 2;
        $OrderLineData->save();
        event(new OrderLineUpdated($OrderLineData->id));
    }
    

    // Helper method to remove from stock
    private function removeFromStock($OrderLineData)
    {
        $quantityRemaining = $OrderLineData->qty;
        $StockLocationProduct = StockLocationProducts::where('products_id', $OrderLineData->product_id)->get();
        foreach ($StockLocationProduct as $stock) {
            $quantityToWithdraw = min($stock->getCurrentStockMove(), $quantityRemaining);
            if ($quantityToWithdraw != 0) {
                StockMove::create([
                    'user_id' => Auth::id(),
                    'qty' => $quantityToWithdraw,
                    'stock_location_products_id' => $stock->id,
                    'order_line_id' => $OrderLineData->id,
                    'typ_move' => 9,
                ]);
            }
            $quantityRemaining -= $quantityToWithdraw;
            if ($quantityRemaining <= 0) {
                break;
            }
        }
    }

    public function storeDelevery($orderId)
    {
        if (!$this->linesExist()) {
            $errors = $this->getErrorBag();
            $errors->add('errors', 'no lines selected');
            return;
        }

        $OrderData = Orders::find($orderId);
        $LastDelivery = Deliverys::orderBy('id', 'desc')->first();
        $deliveryCode = $LastDelivery ? "DN-" . $LastDelivery->id : "DN-0";

        $DeliveryCreated = Deliverys::create([
            'uuid' => Str::uuid(),
            'code' => $deliveryCode,
            'label' => $deliveryCode,
            'companies_id' => $OrderData->companies_id,
            'companies_addresses_id' => $OrderData->companies_addresses_id,
            'companies_contacts_id' => $OrderData->companies_contacts_id,
            'user_id' => Auth::id(),
        ]);

        if ($DeliveryCreated) {
            foreach ($this->data as $key => $item) {
                $OrderLineData = OrderLines::find($key);
                $this->deliveryLineService->createDeliveryLine($DeliveryCreated, $key, $this->deleveryOrdre, $OrderLineData->delivered_remaining_qty);

                if ($this->CreateSerialNumber) {
                    $this->createSerialNumbers($OrderLineData, $OrderLineData->delivered_remaining_qty);
                }

                $this->updateOrderLineInfoFromDelevery($OrderLineData);

                if ($this->RemoveFromStock && $OrderLineData->product_id && $OrderLineData->Task()->get()->isEmpty()) {
                    $this->removeFromStock($OrderLineData);
                }

                $this->deleveryOrdre += 10;
            }
            return redirect()->route('deliverys.show', ['id' => $DeliveryCreated->id])->with('success', 'Successfully created new delivery note');
        } else {
            return redirect()->back()->with('error', 'Something went wrong');
        }
    }

    public function storeInvoice($orderId)
    {
        if (!$this->linesExist()) {
            $errors = $this->getErrorBag();
            $errors->add('errors', 'no lines selected');
            return;
        }

        $OrderData = Orders::find($orderId);
        $LastInvoice = Invoices::orderBy('id', 'desc')->first();
        $invoiceCode = $LastInvoice ? "IN-" . $LastInvoice->id : "IN-0";

        $InvoiceCreated = Invoices::create([
            'uuid' => Str::uuid(),
            'code' => $invoiceCode,
            'label' => $invoiceCode,
            'companies_id' => $OrderData->companies_id,
            'companies_addresses_id' => $OrderData->companies_addresses_id,
            'companies_contacts_id' => $OrderData->companies_contacts_id,
            'user_id' => Auth::id(),
            'due_date' => Carbon::now()->addDays(30),
        ]);

        if ($InvoiceCreated) {
            foreach ($this->data as $key => $item) {
                $OrderLineData = OrderLines::find($key);
                $this->invoiceLineService->createInvoiceLine($InvoiceCreated, $key, null, $this->invoiceOrdre, $OrderLineData->invoiced_remaining_qty);

                if ($this->CreateSerialNumber) {
                    $this->createSerialNumbers($OrderLineData, $OrderLineData->invoiced_remaining_qty);
                }

                $this->updateOrderLineInfoFromDelevery($OrderLineData);

                $this->updateOrderLineInfoFromInvoice($OrderLineData);

                if ($this->RemoveFromStock && $OrderLineData->product_id && $OrderLineData->Task()->get()->isEmpty()) {
                    $this->removeFromStock($OrderLineData);
                }

                $this->invoiceOrdre += 10;
            }
            return redirect()->route('invoices.show', ['id' => $InvoiceCreated->id])->with('success', 'Successfully created new invoice');
        } else {
            return redirect()->back()->with('error', 'Something went wrong');
        }
    }
}