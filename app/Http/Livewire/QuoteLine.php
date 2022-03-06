<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Admin\Factory;
use App\Models\Planning\Task;
use App\Models\Planning\Status;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use App\Models\Products\Products;
use App\Models\Workflow\Quotelines;
use App\Models\Methods\MethodsUnits;
use Illuminate\Support\Facades\Auth;
use App\Models\Methods\MethodsServices;
use App\Models\Accounting\AccountingVat;
use App\Models\Workflow\OrderLines;
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
    public $status_id;

    public $QuoteLineslist;
    public $quote_lines_id, $quotes_id, $ordre, $code, $product_id, $qty, $methods_units_id, $selling_price, $accounting_vats_id, $delivery_date, $statu;
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


    public $data = [];

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
        $QuoteLineslist = $this->QuoteLineslist = Quotelines::orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')->where('quotes_id', '=', $this->quotes_id)->where('label','like', '%'.$this->search.'%')->get();
        
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
        Quotelines::create([
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

    public function cancel()
    {
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
                'quote_id'=>$QuoteData->id, 
            ]);

            if($OrdersCreated){
                // Create lines
                foreach ($this->data as $key => $item) {

                    //get data to dulicate for new order
                    $QuoteLineData = Quotelines::find($this->data[$key]['quote_line_id']);

                    Orderlines::create([
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
                        'delivery_date'=>$QuoteLineData->delivery_date,
                    ]);

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
            return redirect()->route('order.show', ['id' => $OrdersCreated->id])->with('success', 'Successfully created new order');

        }
        else{
            return redirect()->back()->with('error', 'no lines selected');
        }

    }
}
