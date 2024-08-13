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
    public function index(Request $request)
    {
        $countTaskNullDate = Task::whereNull('end_date')
                                ->whereNotNull('order_lines_id')
                                ->where(function (Builder $query) {
                                    return $query->where('tasks.type', 1)
                                                ->orWhere('tasks.type', 7);
                                })
                                ->count();
        
        $OrderLineList = OrderLines::orderby('created_at', 'desc')->get();

        
        $orderLineId = $request->input('order_line_id');

        return view('workflow/gantt-index', compact('countTaskNullDate', 'OrderLineList', 'orderLineId'));
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTasksByOrderLine($order_lines_id)
    {

        // Récupérer la ligne de commande
        $orderLine = OrderLines::find($order_lines_id);

        // Ajouter la ligne de commande comme première "tâche"
        $formattedTasks[] = [
            'id' => 'order_line_' . $orderLine->id,
            'label' => '#' . $orderLine->label,
            'resource' => 'Order Line',
            'start_date' => $orderLine->created_at ? new \DateTime($orderLine->created_at) : null,
            'end_date' => $orderLine->delivery_date ? new \DateTime($orderLine->delivery_date) : null,
            'duration' => null,  // Optionnel, si vous avez besoin de la durée
            'progress' => $orderLine->getAveragePercentProgressTaskAttribute(),
            'dependencies' => null,
        ];

        // Récupérer les tâches liées à un order_lines_id
        $tasks = Task::where('order_lines_id', $order_lines_id)
                    ->where(function (Builder $query) {
                        return $query->where('tasks.type', 1)
                                    ->orWhere('tasks.type', 7);
                    })
                    ->orderBy('ordre', 'asc')
                    ->get();

                    $previousTaskId = 'order_line_' . $orderLine->id; // La première tâche dépend de la ligne de commande

                    // Ajouter les tâches en les reliant correctement
                    foreach ($tasks as $task) {
                        $formattedTasks[] = [
                            'id' => $task->id,
                            'label' => '#'. $task->id .' - '. $task->label,
                            'resource' => $task->service ? $task->service->name : null,
                            'start_date' => $task->start_date ? new \DateTime($task->start_date) : null,
                            'end_date' => $task->end_date ? new \DateTime($task->end_date) : null,
                            'duration' => $task->TotalTime(),
                            'progress' => $task->progress(),
                            'dependencies' => $previousTaskId,  // Dépend de la tâche précédente
                        ];
                
                        $previousTaskId = $task->id; // Mettre à jour l'ID de la tâche précédente pour la prochaine itération
                    }
                
                    return response()->json(['data' => $formattedTasks]);
    }
}
