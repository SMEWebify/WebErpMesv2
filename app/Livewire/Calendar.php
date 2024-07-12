<?php

namespace App\Livewire;

use App\Models\Planning\Task;
use Livewire\Component;
use App\Models\Workflow\Orders;
use Illuminate\Support\Facades\DB;

class Calendar extends Component
{
    public $events = '';
    public $eventType;

    public function mount($eventType)
    {
        $this->eventType = $eventType;
    }

    public function render()
    {
        if ($this->eventType === 'orders') {
            $events = Orders::select('id', 'code AS title', 'validity_date AS start', 'statu AS color', DB::raw("1 as url"))
                ->get()
                ->map(function ($order) {
                    $order->color = str_replace('2', "#ffc107", $order->color);
                    $order->color = str_replace('3', "#28a745", $order->color);
                    $order->url = route('orders.show', $order->id);
                    return $order;
                });
        } else {
            $events = Task::select('id',  DB::raw('CONCAT(\' #\',id, \' - \', label) AS title'), 'end_date AS start', 'status_id AS color', DB::raw("1 as url"))
                ->where(function ($query) { //https://github.com/SMEWebify/WebErpMesv2/issues/334
                    $query->whereNotNull('sub_assembly_id') // Tasks with non-null sub_assembly_id
                        ->WhereHas('SubAssembly', function ($query) {
                            $query->whereNotNull('order_lines_id'); // Subassemblies with non-null order_lines_id
                        });
                })
                ->orwhereNotNull('order_lines_id') // Tasks with non-null order_lines_id
                ->get()
                ->map(function ($task) {
                    $task->color = str_replace('2', "#ffc107", $task->color);
                    $task->color = str_replace('3', "#28a745", $task->color);
                    $task->url = route('production.task.statu.id', $task->id);
                    return $task;
                });
        }

        $this->events = json_encode($events);

        return view('livewire.calendar');
    }
}
