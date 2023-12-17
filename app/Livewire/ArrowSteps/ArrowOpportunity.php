<?php

namespace App\Livewire\ArrowSteps;

use App\Models\Workflow\Opportunities;
use Livewire\Component;

class ArrowOpportunity extends Component
{
    public $OpportunityId;
    public $OpportunityStatu;

    public function mount($OpportunityId, $OpportunityStatu) 
    {
        $this->OpportunityId = $OpportunityId;
        $this->OpportunityStatu = $OpportunityStatu;
    }

    public function render()
    {
        return view('livewire.arrow-steps.arrow-opportunity');
    }

    public function changeStatu($statuNumber){
        try{
            Opportunities::where('id',$this->OpportunityId)->update(['statu'=>$statuNumber]);
            return redirect()->route('opportunities.show', ['id' =>  $this->OpportunityId])->with('success', 'Successfully updated statu');
        }catch(\Exception $e){
            session()->flash('error',"Something goes wrong on update statu");
        }
    }
}
