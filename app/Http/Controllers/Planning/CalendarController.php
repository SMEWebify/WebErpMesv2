<?php

namespace App\Http\Controllers\Planning;

use App\Models\Planning\Task;
use App\Models\Workflow\Orders;
use Illuminate\Support\Facades\DB;

class CalendarController extends Controller
{
    public function calendarOders()
    {
        return view('workflow/calendar-index', ['eventType' => 'orders']);
    }

    public function calendarTasks()
    {
        return view('workflow/calendar-index', ['eventType' => 'tasks']);
    }

    public function eventsOrders()
    {
        $events = Orders::select('id', 'code AS title', 'validity_date AS start', 'statu AS color', DB::raw("1 as url"))
            ->get()
            ->map(function ($order) {
                $order->color = str_replace('2', '#ffc107', $order->color);
                $order->color = str_replace('3', '#28a745', $order->color);
                $order->url   = route('orders.show', $order->id);
                return $order;
            });

        return response()->json($events);
    }

    public function eventsTasks()
    {
        $events = Task::select('id', DB::raw("CONCAT(' #', id, ' - ', label) AS title"), 'end_date AS start', 'status_id AS color', DB::raw("1 as url"))
            ->where(function ($query) {
                $query->whereNotNull('sub_assembly_id')
                      ->whereHas('SubAssembly', fn ($q) => $q->whereNotNull('order_lines_id'));
            })
            ->orWhereNotNull('order_lines_id')
            ->get()
            ->map(function ($task) {
                $task->color = str_replace('2', '#ffc107', $task->color);
                $task->color = str_replace('3', '#28a745', $task->color);
                $task->url   = route('production.task.statu.id', $task->id);
                return $task;
            });

        return response()->json($events);
    }
}
