<?php

namespace App\Livewire\ArrowSteps;

use App\Models\Purchases\Purchases;
use Livewire\Component;

class ArrowPurchase extends Component
{
    public $PurchaseId;
    public $PurchaseStatu;

    public function mount($PurchaseId,  $PurchaseStatu) 
    {
        $this->PurchaseId = $PurchaseId;
        $this->PurchaseStatu = $PurchaseStatu;
    }

    public function render()
    {
        return view('livewire.arrow-steps.arrow-purchase');
    }

    public function changeStatu($statuNumber){
        try{
            Purchases::where('id',$this->PurchaseId)->update(['statu'=>$statuNumber]);
            return redirect()->route('purchases.show', ['id' =>  $this->PurchaseId])->with('success', 'Successfully updated statu');
        }catch(\Exception $e){
            session()->flash('error',"Something goes wrong on update statu");
        }
    }
}
