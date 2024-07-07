<?php

namespace App\Http\Controllers\Planning;

use Illuminate\Http\Request;
use App\Models\Planning\Task;
use App\Models\Workflow\Orders;
use Illuminate\Support\Facades\DB;
use App\Models\Workflow\OrderLines;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;

class GanttController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $countTaskNullDate = Task::whereNull('end_date')
                                ->whereNotNull('order_lines_id')
                                ->where(function (Builder $query) {
                                    return $query->where('tasks.type', 1)
                                                ->orWhere('tasks.type', 7);
                                })
                                ->count();
        return view('workflow/gantt-index', compact('countTaskNullDate'));
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function get(){

        $Orders = Orders::select('orders.id', DB::raw('orders.code as text'), DB::raw("0 AS duration"), DB::raw("0 as progress"), 'orders.validity_date AS end_date', DB::raw("0 as parent"))
                                ->where('orders.statu', '=', 1)
                                ->Orwhere('orders.statu', '=', 2)
                                ->orderBy('orders.validity_date')
                                ->get()->map(function($tag){
                                    return [
                                        'id' => $tag->id,
                                        'text' => $tag->text,
                                        'duration' => $tag->duration,
                                        'progress' => $tag->progress,
                                        'end_date' => $tag->end_date,
                                        'parent' => $tag->parent,
                                    ];
                                });

        $OrderLines = OrderLines::select(DB::raw('CONCAT(\'l_\',order_lines.id) AS id_tempo'), DB::raw('CONCAT(order_lines.code, \' \', order_lines.label)   as text'), DB::raw("0 AS duration"), DB::raw("0 as progress"), 'order_lines.internal_delay AS end_date', DB::raw('order_lines.orders_id as parent'))
                                ->join('orders', 'order_lines.orders_id', '=', 'orders.id')
                                ->where('orders.statu', '=', 1)
                                ->Orwhere('orders.statu', '=', 2)
                                ->orderBy('order_lines.internal_delay')
                                ->get()->map(function($tag){
                                    return [
                                        'id' => $tag->id_tempo,
                                        'text' => $tag->text,
                                        'duration' => $tag->duration,
                                        'progress' => $tag->progress,
                                        'end_date' => $tag->end_date,
                                        'parent' => $tag->parent,
                                    ];
                                });
        $merge = $Orders->merge($OrderLines);
        
        $tasks = Task::select('tasks.id', DB::raw('CONCAT(\'#\',tasks.id, \' \', tasks.label) as text'), DB::raw("(tasks.qty * tasks.unit_time + tasks.seting_time) AS duration"),  'tasks.end_date AS end_date', DB::raw('CONCAT(\'l_\',tasks.order_lines_id) as parent'), DB::raw("methods_services.color as color"))
                                ->join('order_lines', 'tasks.order_lines_id', '=', 'order_lines.id')
                                ->join('orders', 'order_lines.orders_id', '=', 'orders.id')
                                ->join('methods_services', 'tasks.methods_services_id', '=', 'methods_services.id')
                                ->whereNotNull('tasks.order_lines_id')
                                ->where(function (Builder $query) {
                                    return $query->where('tasks.type', 1)
                                                ->orWhere('tasks.type', 7);
                                })
                                ->where('orders.statu', 1)
                                ->Orwhere('orders.statu', '=', 2)
                                ->orderBy('tasks.end_date')
                                ->get()->map(function($tag){
                                    return [
                                        'id' => $tag->id,
                                        'text' => $tag->text,
                                        'duration' => $tag->duration,
                                        'end_date' => $tag->end_date,
                                        'parent' => $tag->parent,
                                        'color' => $tag->color,
                                    ];
                                });
        $merge = $merge->merge($tasks);
        
        return response()->json([
            "data" => $merge,
        ]);
    }
}
