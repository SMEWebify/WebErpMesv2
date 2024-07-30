<?php

namespace App\Livewire\ArrowSteps;

use Livewire\Component;
use App\Models\Workflow\Quotes;
use App\Events\QuoteStatusChanged;
use App\Models\Workflow\QuoteLines;

class ArrowQuote extends Component
{
    public $QuoteId;
    public $QuoteStatu;

    public function mount($QuoteId, $QuoteStatu) 
    {
        $this->QuoteId = $QuoteId;
        $this->QuoteStatu = $QuoteStatu;
    }

    public function render()
    {
        return view('livewire.arrow-steps.arrow-quote');
    }

    public function changeStatu($statuNumber){
        try{
            Quotes::where('id',$this->QuoteId)->update(['statu'=>$statuNumber]);
            QuoteLines::where('quotes_id', $this->QuoteId)->update(['statu' => $statuNumber]);

            // event for opp statu
            event(new QuoteStatusChanged($this->QuoteId, $statuNumber));

            return redirect()->route('quotes.show', ['id' =>  $this->QuoteId])->with('success', 'Successfully updated statu');
        }catch(\Exception $e){
            session()->flash('error',"Something goes wrong on update statu");
        }
    }
}
