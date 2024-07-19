<?php

namespace App\Livewire\ArrowSteps;

use Livewire\Component;
use App\Models\Workflow\Orders;

class ArrowOrder extends Component
{
    public $OrderId;
    public $OrderType;
    public $OrderStatu;

    public function mount($OrderId, $OrderType, $OrderStatu) 
    {
        $this->OrderId = $OrderId;
        $this->OrderType = $OrderType;
        $this->OrderStatu = $OrderStatu;
    }

    public function render()
    {
        return view('livewire.arrow-steps.arrow-order');
    }

    public function changeStatu($statuNumber){
        try{
            Orders::where('id',$this->OrderId)->update(['statu'=>$statuNumber]);
            return redirect()->route('orders.show', ['id' =>  $this->OrderId])->with('success', 'Successfully updated statu');
        }catch(\Exception $e){
            session()->flash('error',"Something goes wrong on update statu");
        }
    }
}
