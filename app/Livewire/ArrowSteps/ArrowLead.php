<?php

namespace App\Livewire\ArrowSteps;

use App\Models\Workflow\Leads;
use Livewire\Component;

class ArrowLead extends Component
{
    public $LeadId;
    public $LeadStatu;

    public function mount($LeadId, $LeadStatu) 
    {
        $this->LeadId = $LeadId;
        $this->LeadStatu = $LeadStatu;
    }

    public function render()
    {
        return view('livewire.arrow-steps.arrow-lead');
    }

    public function changeStatu($statuNumber){
        try{
            Leads::where('id',$this->LeadId)->update(['statu'=>$statuNumber]);
            return redirect()->route('leads.show', ['id' =>  $this->LeadId])->with('success', 'Successfully updated statu');
        }catch(\Exception $e){
            session()->flash('error',"Something goes wrong on update statu");
        }
    }
}
