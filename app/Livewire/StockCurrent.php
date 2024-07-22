<?php

namespace App\Livewire;

use Carbon\Carbon;
use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Str;
use App\Models\Admin\Factory;
use App\Models\Planning\Task;
use App\Models\Workflow\Orders;
use App\Models\Products\Products;
use App\Models\Workflow\OrderLines;
use App\Models\Planning\SubAssembly;
use Illuminate\Support\Facades\Auth;
use App\Models\Accounting\AccountingVat;
use App\Notifications\OrderNotification;
use App\Models\Workflow\OrderLineDetails;
use Illuminate\Support\Facades\Notification;

class StockCurrent extends Component
{

    public $produitsAvecStock = [];
    public $Factory = [];
    
    public function mount()
    {
        $this->Factory = Factory::first();
    }

    public function render()
    {

        return view('livewire.stock-current');
    }

    public function showStock()
    {
        // Définissez $showStock sur vrai pour afficher la liste des stocks
        $this->produitsAvecStock = Products::with('Stock_location_product')->get();
    }

    public function storeOrder($productId, $productQuantity){

        $dateFormatted = Carbon::now()->format('d-m-y');
        //For the moment, we define the internal order deadline at +1 week
        $mutable = Carbon::now();
        $validity_date = $mutable->add(7, 'day');

        //Select default vat for define VAT on orderline
        $accountingVatDefaultId = AccountingVat::select('id')->where( 'default', 1)->first(); 
        $accountingVatDefaultId = ($accountingVatDefaultId->id ?? 0); 

        if($accountingVatDefaultId == 0 ){
            return redirect()->route('products.stock')->with('error', 'No default settings');
        }

        //Get Product info for put in new order
        $ProductDetail = Products::findOrFail($productId);
        $sellingPrice = ($ProductDetail->selling_price ?? 0); 
        // Create Line
        $OrdersCreated = Orders::create([
                                        'uuid'=> Str::uuid(),
                                        'code'=> 'INT-STOCK-' . $dateFormatted,  
                                        'label'=>'INT-STOCK-' . $dateFormatted,  
                                        'companies_id'=>0,  
                                        'companies_contacts_id'=> 0,    
                                        'companies_addresses_id'=> 0,   
                                        'validity_date'=>$validity_date,  
                                        'user_id'=> Auth::id(),   
                                        'accounting_payment_conditions_id'=> 0,   
                                        'accounting_payment_methods_id'=> 0,   
                                        'accounting_deliveries_id'=> 0,   
                                        'type'=>2, 
        ]);

        $date = date_create($validity_date);
        $internalDelay = date_format(date_sub($date , date_interval_create_from_date_string($this->Factory->add_delivery_delay_order. " days")), 'Y-m-d');
                    

        $newOrderline = Orderlines::create([
            'orders_id'=>$OrdersCreated->id,
            'ordre'=>10,
            'code'=>$ProductDetail->code,
            'product_id'=>$ProductDetail->id,
            'label'=>$ProductDetail->label,
            'qty'=>$productQuantity,
            'delivered_remaining_qty'=>$productQuantity,
            'invoiced_remaining_qty'=>$productQuantity,
            'methods_units_id'=>$ProductDetail->methods_units_id,
            'selling_price'=>$sellingPrice,
            'discount'=>0,
            'accounting_vats_id'=>$accountingVatDefaultId,
            'internal_delay'=>$internalDelay,
            'delivery_date'=>$validity_date,
        ]);

        //add line detail
        $newOrderLineDetail = OrderLineDetails::create([
            'order_lines_id'=>$newOrderline->id,
            'x_size'=>$ProductDetail->x_size,
            'y_size'=>$ProductDetail->y_size,
            'z_size'=>$ProductDetail->z_size,
            'x_oversize'=>$ProductDetail->x_oversize,
            'y_oversize'=>$ProductDetail->y_oversize,
            'z_oversize'=>$ProductDetail->z_oversize,
            'diameter'=>$ProductDetail->diameter,
            'diameter_oversize'=>$ProductDetail->diameter_oversize,
            'material'=>$ProductDetail->material,
            'thickness'=>$ProductDetail->thickness,
            'finishing'=>$ProductDetail->finishing,
            'weight'=>$ProductDetail->weight,
            'material_loss_rate'=>0,
            'cad_file'=>$ProductDetail->drawing_file,
        ]);

        $TaskLine = Task::where('products_id', $productId)->get();
        foreach ($TaskLine as $Task) 
        {
            $newTask = $Task->replicate();
            $newTask->order_lines_id = $newOrderline->id;
            $newTask->products_id = null;
            $newTask->save();
        }
        $SubAssemblyLine = SubAssembly::where('products_id', $productId)->get();
        foreach ($SubAssemblyLine as $SubAssembly) 
        {
            $newSubAssembly = $SubAssembly->replicate();
            $newTask->order_lines_id = $newOrderline->id;
            $newSubAssembly->products_id = null;
            $newSubAssembly->save();
        }

        // notification for all user in database
        $users = User::where('orders_notification', 1)->get();
        Notification::send($users, new OrderNotification($OrdersCreated));

        return redirect()->route('orders.show', ['id' => $OrdersCreated->id])->with('success', 'Successfully created new internal order');
    }
}
