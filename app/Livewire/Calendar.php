<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Workflow\Orders;
use Illuminate\Support\Facades\DB;

class Calendar extends Component
{
    public $events = '';

    public function render()
    {
        $events =Orders::select('id', 'code AS title', 'validity_date AS start', 'statu AS color', DB::raw("1 as url"))
                            ->get()
                            ->map(function ($order) {
                                $order->color = str_replace('2', "#ffc107", $order->color);
                                $order->color = str_replace('3', "#28a745", $order->color);
                                
                                $order->url = route('orders.show', $order->id);
                                return $order;
                            });
        $this->events = json_encode($events);
        return view('livewire.calendar');
    }
}
