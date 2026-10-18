<?php

namespace App\Http\Controllers\Planning;

use Illuminate\Http\Request;
use App\Models\Planning\Task;
use App\Models\Workflow\Orders;
use App\Models\Workflow\OrderLines;
use App\Http\Controllers\Controller;
use App\Services\Planning\DependencyEdge;
use App\Services\Planning\DependencyGraph;
use Illuminate\Database\Eloquent\Builder;

class GanttController extends Controller
{
    public function index(Request $request)
    {
        $countTaskNullDate = Task::whereNull('end_date')
            ->whereNotNull('order_lines_id')
            ->where(function (Builder $query) {
                $query->where('tasks.type', 1)->orWhere('tasks.type', 7);
            })
            ->count();

        $orderList = Orders::orderBy('created_at', 'desc')->get(['id', 'code', 'label']);

        $orderId = $request->input('order_id');

        return view('workflow/gantt-index', compact('countTaskNullDate', 'orderList', 'orderId'));
    }

    public function getTasksByOrder(int $order_id)
    {
        $order = Orders::with([
            'orderLines' => function ($q) {
                $q->orderBy('ordre');
            },
        ])->findOrFail($order_id);

        $formattedTasks = [];

        // Root bar: the order itself
        $formattedTasks[] = [
            'id'           => 'order_' . $order->id,
            'label'        => '#' . $order->code . ($order->label ? ' — ' . $order->label : ''),
            'resource'     => 'Order',
            'start_date'   => $order->created_at ? new \DateTime($order->created_at) : null,
            'end_date'     => null,
            'duration'     => null,
            'progress'     => 0,
            'dependencies' => null,
        ];

        foreach ($order->orderLines as $orderLine) {

            $previousId = 'order_' . $order->id;

            // Bar for the order line
            $formattedTasks[] = [
                'id'           => 'ol_' . $orderLine->id,
                'label'        => '#' . $orderLine->id . ' ' . $orderLine->label,
                'resource'     => 'Order Line',
                'start_date'   => $orderLine->created_at ? new \DateTime($orderLine->created_at) : null,
                'end_date'     => $orderLine->delivery_date ? new \DateTime($orderLine->delivery_date) : null,
                'duration'     => null,
                'progress'     => $orderLine->getAveragePercentProgressTaskAttribute(),
                'dependencies' => $previousId,
            ];

            // Tasks of this order line (type 1 or 7), ordered by ordre
            $tasks = Task::with(['service', 'predecessorLinks'])
                ->where('order_lines_id', $orderLine->id)
                ->where(function (Builder $query) {
                    $query->where('tasks.type', 1)->orWhere('tasks.type', 7);
                })
                ->orderBy('ordre')
                ->orderBy('id')
                ->get();

            // Flèches = liens effectifs du calcul des dates (CalculateTaskDates) :
            // liens saisis, sinon l'opération précédente. Liens désactivés : chaîne.
            if (! config('planning.dependencies_enabled', true)) {
                $tasks->each(fn (Task $task) => $task->setRelation('predecessorLinks', collect()));
            }
            $graph = DependencyGraph::fromTasks($tasks);

            foreach ($graph->tasks() as $id => $task) {
                $from = array_map(fn (DependencyEdge $edge) => 'task_' . $edge->from, $graph->incoming($id));

                $formattedTasks[] = [
                    'id'           => 'task_' . $task->id,
                    'label'        => '#' . $task->id . ' — ' . $task->label,
                    'resource'     => $task->service?->name ?? null,
                    'start_date'   => $task->start_date ? new \DateTime($task->start_date) : null,
                    'end_date'     => $task->end_date ? new \DateTime($task->end_date) : null,
                    'duration'     => $task->TotalTime(),
                    'progress'     => $task->progress(),
                    // Sans amont, l'opération part de la ligne de commande.
                    'dependencies' => $from !== [] ? implode(',', array_unique($from)) : 'ol_' . $orderLine->id,
                ];
            }
        }

        return response()->json(['data' => $formattedTasks]);
    }
}
