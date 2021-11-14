<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Admin\Factory;
use App\Models\Planning\Task;
use App\Models\Products\Products;
use App\Models\workflow\Orderlines;
use App\Models\Methods\MethodsUnits;
use App\Models\Methods\MethodsServices;
use App\Models\Accounting\AccountingVat;


class OrderLine extends Component
{

    public $OrderId;
    public $OrderStatu;

    public $OrderLineslist;
    public $order_lines_id, $orders_id, $ORDRE, $CODE, $product_id, $LABEL, $qty, $methods_units_id, $selling_price, $discount, $accounting_vats_id, $delivery_date, $statu;
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
        'CODE' =>'required',
        'ORDRE'=>'required',
        'qty'=>'required',
        'methods_units_id'=>'required',
        'selling_price'=>'required',
        'discount'=>'required',
        'accounting_vats_id'=>'required',
    ];

    public function mount($OrderId, $OrderStatu) 
    {
        $this->orders_id = $OrderId;
        $this->order_Statu = $OrderStatu;
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
        $OrderLineslist = $this->OrderLineslist = Orderlines::orderBy('ORDRE')->where('orders_id', '=', $this->orders_id)->get();
        
        return view('livewire.order-lines', [
            'OrderLineslist' => $OrderLineslist,
        ]);
    }

    public function resetFields(){
        $this->ORDRE = $this->ORDRE+1;
        $this->CODE = '';
        $this->product_id = '';
        $this->LABEL = '';
        $this->qty = '';
        $this->selling_price = '';
    }

    public function storeOrderLine(){

        $this->validate();

            // Create Category
            Orderlines::create([
                'orders_id'=>$this->orders_id,
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

    public function update(){

        // Validate request
        $this->validate();

        //try{

            // Update line
            Orderlines::find($this->order_lines_id)->fill([
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
    
          //  $this->cancel();
        //}catch(\Exception $e){

         //   session()->flash('error','Something goes wrong while updating line');
         //   $this->cancel();
        //}
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