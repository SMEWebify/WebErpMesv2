<?php

namespace App\Http\Livewire;

use App\Models\Workflow\Orders;
use Livewire\Component;

class Calendar extends Component
{
    public $events = '';

    public function render()
    {
        $events =Orders::select('id', 'code AS title', 'validity_date AS start')->get();
        $this->events = json_encode($events);
                                            
        return view('livewire.calendar');
    }
}
