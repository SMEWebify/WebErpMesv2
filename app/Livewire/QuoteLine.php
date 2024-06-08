<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use App\Models\Admin\Factory;
use App\Models\Planning\Task;
use App\Models\Planning\Status;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use App\Models\Products\Products;
use App\Models\Companies\Companies;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\QuoteLines;
use App\Models\Methods\MethodsUnits;
use App\Models\Planning\SubAssembly;
use Illuminate\Support\Facades\Auth;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsServices;
use App\Models\Accounting\AccountingVat;
use App\Notifications\OrderNotification;
use App\Models\Workflow\OrderLineDetails;
use App\Models\Workflow\QuoteLineDetails;
use Illuminate\Support\Facades\Notification;
use League\CommonMark\Extension\SmartPunct\Quote;

class QuoteLine extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $sortField = 'ordre'; // default sorting field
    public $sortAsc = true; // default sort direction
    
    public $QuoteId;
    public $QuoteStatu;
    public $quote_Statu;
    public $status_id;

    public $QuoteLineslist;
    public $quote_lines_id, $quotes_id, $ordre, $product_id, $methods_units_id, $selling_price, $accounting_vats_id, $delivery_date, $statu;
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

    // Validation Rules
    protected $rules = [
        'ordre' =>'required|numeric|min:0|not_in:0',
        'label'=>'required',
        'qty'=>'required|min:1|not_in:0',
        'methods_units_id'=>'required',
        'selling_price'=>'required|numeric|min:0|not_in:0',
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

    public function mount($QuoteId, $QuoteStatu, $QuoteDelay) 
    {
        $this->quotes_id = $QuoteId;
        $this->quote_Statu = $QuoteStatu;
        $this->delivery_date = $QuoteDelay;
        $this->status_id = Status::select('id')->orderBy('order')->first();
        $this->Factory = Factory::first();
        $this->ProductsSelect = Products::select('id', 'label', 'code')->orderBy('code')->get();
        $this->VATSelect = AccountingVat::select('id', 'label', 'default')->orderBy('rate')->get();
        $this->UnitsSelect = MethodsUnits::select('id', 'label', 'code', 'default')->orderBy('label')->get();
        $this->ProductSelect = Products::select('id', 'code','label', 'methods_services_id')->get();
    }

    public function render()
    {
        $QuoteLineslist = $this->QuoteLineslist = Quotelines::orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                                                            ->where('quotes_id', '=', $this->quotes_id)
                                                            ->where('label','like', '%'.$this->search.'%')->get();
        
        return view('livewire.quote-lines', [
            'QuoteLineslist' => $QuoteLineslist,
        ]);
    }

    public function resetFields(){
        $this->ordre = $this->ordre+1;
        $this->code = '';
        $this->product_id = '';
        $this->label = '';
    }

    public function storeQuoteLine(){
        $this->validate();
        // Create Line
        $NewQuoteLine = Quotelines::create([
            'quotes_id'=>$this->quotes_id,
            'ordre'=>$this->ordre,
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
        
        //add line detail
        QuoteLineDetails::create(['quote_lines_id'=>$NewQuoteLine->id]);
        
        // Set Flash Message
        session()->flash('success','Line added Successfully');
        // Reset Form Fields After Creating line
        $this->resetFields();
    }

    public function editQuoteLine($id){
        $Line = Quotelines::findOrFail($id);
        $this->quote_lines_id = $id;
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

    public function updateQuoteLine(){
        // Validate request
        $this->validate();
        // Update line
        Quotelines::find($this->quote_lines_id)->fill([
            'ordre'=>$this->ordre,
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

    
    public function duplicateLine($id){
        $Quoteline = Quotelines::findOrFail($id);
        $newQuoteline = $Quoteline->replicate();
        $newQuoteline->ordre = $Quoteline->ordre+1;
        $newQuoteline->code = $Quoteline->code ."#duplicate". $Quoteline->id;
        $newQuoteline->label = $Quoteline->label ."#duplicate". $Quoteline->id;
        $newQuoteline->save();

        $QuotelineDetails = QuoteLineDetails::where('quote_lines_id', $id)->first();
        $newQuotelineDetails = $QuotelineDetails->replicate();
        $newQuotelineDetails->quote_lines_id = $newQuoteline->id;
        $newQuotelineDetails->save();

        $Tasks = Task::where('quote_lines_id', $id)->get();
        foreach ($Tasks as $Task) 
        {
            $newTask = $Task->replicate();
            $newTask->quote_lines_id = $newQuoteline->id;
            $newTask->save();
        }
        $SubAssemblyLine = SubAssembly::where('quote_lines_id', $id)->get();
        foreach ($SubAssemblyLine as $SubAssembly) 
        {
            $newSubAssembly = $SubAssembly->replicate();
            $newSubAssembly->quote_lines_id = $newQuoteline->id;
            $newSubAssembly->save();
        }
    }
    
    public function CreatProduct($id){

        $ServiceComponent = MethodsServices::where('type', 8)->first();
        $FamilieComponent = MethodsFamilies::where('methods_services_id', $ServiceComponent->id)->first();

        if(!empty($ServiceComponent->id) && !empty($FamilieComponent->id)){
            //get data to dulicate for new order
            $QuoteLineData = Quotelines::find($id);
            $newProduct = Products::create([
                'code'=>$QuoteLineData->code,
                'label'=>$QuoteLineData->label,
                'methods_services_id'=> $ServiceComponent->id,
                'methods_families_id'=> $FamilieComponent->id,
                'purchased'=> 2,
                'purchased_price'=> 1,
                'sold'=> 1,
                'selling_price'=> $QuoteLineData->selling_price,
                'methods_units_id'=>$QuoteLineData->methods_units_id,
                'tracability_type'=>1,
                
            ]);
            //update info that order line as task
            $QuoteLineData->product_id = $newProduct->id;
            $QuoteLineData->save();

            //add line detail
            $QuoteLineDetailData = QuoteLineDetails::where('quote_lines_id', $id)->first();
            $newProductDetail = Products::findOrFail($newProduct->id);
            $newProductDetail->material = $QuoteLineDetailData->material;
            $newProductDetail->thickness = $QuoteLineDetailData->thickness;
            $newProductDetail->finishing = $QuoteLineDetailData->finishing;
            $newProductDetail->weight = $QuoteLineDetailData->weight;
            $newProductDetail->x_size = $QuoteLineDetailData->x_size;
            $newProductDetail->y_size = $QuoteLineDetailData->y_size;
            $newProductDetail->z_size = $QuoteLineDetailData->z_size;
            $newProductDetail->x_oversize = $QuoteLineDetailData->x_oversize;
            $newProductDetail->y_oversize = $QuoteLineDetailData->y_oversize;
            $newProductDetail->z_oversize = $QuoteLineDetailData->z_oversize;
            $newProductDetail->diameter = $QuoteLineDetailData->diameter;
            $newProductDetail->diameter_oversize = $QuoteLineDetailData->diameter_oversize;
            $newProductDetail->save();

            $Tasks = Task::where('quote_lines_id', $id)->get();
            foreach ($Tasks as $Task) 
            {
                $newTask = $Task->replicate();
                $newTask->products_id = $newProduct->id;
                $newTask->quote_lines_id = null;
                $newTask->save();
            }
            
            $SubAssemblyLine = SubAssembly::where('quote_lines_id', $id)->get();
            foreach ($SubAssemblyLine as $SubAssembly) 
            {
                $newSubAssembly = $SubAssembly->replicate();
                $newSubAssembly->products_id = $newProduct->id;
                $newSubAssembly->quote_lines_id = null;
                $newSubAssembly->save();
            }
            
            session()->flash('success','Product created Successfully');
        }
        else{
            session()->flash('error','No component service or family');
        }
    }
    
    public function breakDown($id){
        $Quoteline = Quotelines::findOrFail($id);
        $TaskLine = Task::where('products_id', $Quoteline->product_id)->get();
        foreach ($TaskLine as $Task) 
        {
            $newTask = $Task->replicate();
            $newTask->quote_lines_id = $id;
            $newTask->products_id = null;
            $newTask->save();
        }
        $SubAssemblyLine = SubAssembly::where('products_id', $Quoteline->product_id)->get();
        foreach ($SubAssemblyLine as $SubAssembly) 
        {
            $newSubAssembly = $SubAssembly->replicate();
            $newSubAssembly->quote_lines_id = $id;
            $newSubAssembly->products_id = null;
            $newSubAssembly->save();
        }
    }

    public function cancel(){
        $this->updateLines = false;
        $this->resetFields();
    }

    public function upQuoteLine($idStatu){
        // Update line
        Quotelines::find($idStatu)->increment('ordre',1);;
        session()->flash('success','Line Updated Successfully');
    }

    public function downQuoteLine($idStatu){
        // Update line
        Quotelines::find($idStatu)->decrement('ordre',1);;
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

    public function storeOrder($quoteId){

        //check if line exist
        $i = 0;
        foreach ($this->data as $key => $item) {
            if(array_key_exists("quote_line_id",$this->data[$key])){
                if($this->data[$key]['quote_line_id'] != false ){
                    $i++;
                }
            }
        }

        if($i>0){

            //get data to dulicate for new order
            $QuoteData = Quotes::find($quoteId);

            //get last order id for create new Code id
            $LastOrder =  Orders::orderBy('id', 'desc')->first();
            if($LastOrder == Null){
                $orderCode = "OR-0";
            }
            else{
                $orderCode = "OR-". $LastOrder->id;
            }

             // Create order
            $OrdersCreated = Orders::create([
                'uuid'=> Str::uuid(),
                'code'=>$orderCode,  
                'label'=>$QuoteData->label,  
                'customer_reference'=>$QuoteData->customer_reference, 
                'companies_id'=>$QuoteData->companies_id,  
                'companies_contacts_id'=>$QuoteData->companies_contacts_id,    
                'companies_addresses_id'=>$QuoteData->companies_addresses_id,   
                'validity_date'=>$QuoteData->validity_date,  
                'user_id'=>Auth::id(),   
                'accounting_payment_conditions_id'=>$QuoteData->accounting_payment_conditions_id,   
                'accounting_payment_methods_id'=>$QuoteData->accounting_payment_methods_id,   
                'accounting_deliveries_id'=>$QuoteData->accounting_deliveries_id,   
                'comment'=>$QuoteData->comment,
                'quotes_id'=>$QuoteData->id, 
            ]);

            // notification for all user in database
            $users = User::where('orders_notification', 1)->get();
            Notification::send($users, new OrderNotification($OrdersCreated));

            Companies::where('id', $QuoteData->companies_id)->update(['statu_customer'=>3]);

            if($OrdersCreated){
                // Create lines
                foreach ($this->data as $key => $item) {

                    //get data to dulicate for new order
                    $QuoteLineData = Quotelines::find($key);

                    $date = date_create($QuoteLineData->delivery_date);
                    $internalDelay = date_format(date_sub($date , date_interval_create_from_date_string($this->Factory->add_delivery_delay_order. " days")), 'Y-m-d');
                    
                    $newOrderline = Orderlines::create([
                        'orders_id'=>$OrdersCreated->id,
                        'ordre'=>$QuoteLineData->ordre,
                        'code'=>$QuoteLineData->code,
                        'product_id'=>$QuoteLineData->product_id,
                        'label'=>$QuoteLineData->label,
                        'qty'=>$QuoteLineData->qty,
                        'delivered_remaining_qty'=>$QuoteLineData->qty,
                        'invoiced_remaining_qty'=>$QuoteLineData->qty,
                        'methods_units_id'=>$QuoteLineData->methods_units_id,
                        'selling_price'=>$QuoteLineData->selling_price,
                        'discount'=>$QuoteLineData->discount,
                        'accounting_vats_id'=>$QuoteLineData->accounting_vats_id,
                        'internal_delay'=>$internalDelay,
                        'delivery_date'=>$QuoteLineData->delivery_date,
                    ]);

                    //add line detail
                    $QuoteLineDetailData = QuoteLineDetails::where('quote_lines_id', $key)->first();
                    $newOrderLineDetail = OrderLineDetails::create([
                        'order_lines_id'=>$newOrderline->id,
                        'x_size'=>$QuoteLineDetailData->x_size,
                        'y_size'=>$QuoteLineDetailData->y_size,
                        'z_size'=>$QuoteLineDetailData->z_size,
                        'x_oversize'=>$QuoteLineDetailData->x_oversize,
                        'y_oversize'=>$QuoteLineDetailData->y_oversize,
                        'z_oversize'=>$QuoteLineDetailData->z_oversize,
                        'diameter'=>$QuoteLineDetailData->diameter,
                        'diameter_oversize'=>$QuoteLineDetailData->diameter_oversize,
                        'material'=>$QuoteLineDetailData->material,
                        'thickness'=>$QuoteLineDetailData->thickness,
                        'finishing'=>$QuoteLineDetailData->finishing,
                        'weight'=>$QuoteLineDetailData->weight,
                        'material_loss_rate'=>$QuoteLineDetailData->material_loss_rate,
                        'cad_file'=>$QuoteLineDetailData->cad_file,
                        'internal_comment'=>$QuoteLineDetailData->internal_comment,
                        'external_comment'=>$QuoteLineDetailData->external_comment,
                    ]);

                    $Tasks = Task::where('quote_lines_id', $key)->get();
                    foreach ($Tasks as $Task) 
                    {
                        $newTask = $Task->replicate();
                        $newTask->order_lines_id = $newOrderline->id;
                        $newTask->quote_lines_id = null;
                        $newTask->save();

                        //update info that order line as task
                        $OrderLine = OrderLines::find($newOrderline->id);
                        $OrderLine->tasks_status = 2;
                        $OrderLine->save();
                        
                    }
                    
                    $SubAssemblyLine = SubAssembly::where('quote_lines_id', $key)->get();
                    foreach ($SubAssemblyLine as $SubAssembly) 
                    {
                        $newSubAssembly = $SubAssembly->replicate();
                        $newSubAssembly->order_lines_id = $newOrderline->id;
                        $newSubAssembly->quote_lines_id = null;
                        $newSubAssembly->save();
                    }

                    //update quote lines statu
                    Quotelines::where('id',$QuoteLineData->id)->update(['statu'=>3]);
                }
                //update quote statu
                Quotes::where('id',$quoteId)->update(['statu'=>3]);
                
            }
            else{
                return redirect()->back()->with('error', 'Something went wrong');
            }

            // Reset Form Fields After Creating line
            return redirect()->route('orders.show', ['id' => $OrdersCreated->id])->with('success', 'Successfully created new order');

        }
        else{
            $errors = $this->getErrorBag();
            $errors->add('errors', 'no lines selected');
        }
    }
}
