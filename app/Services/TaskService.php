<?php

namespace App\Services;


use App\Models\Planning\Status;
use App\Models\Planning\Task;
use App\Models\Planning\TaskActivities;
use Illuminate\Support\Facades\Event;
use App\Events\TaskChangeStatu;
use Carbon\Carbon;

class TaskService
{
    public function closeTasks($orderLineId)
    {
        // Récupérer l'ID du statut "Finished"
        $statusUpdate = Status::select('id')->where('title', 'Finished')->first();

        if ($statusUpdate) {
            // Mettre à jour les tâches de la ligne de commande
            $tasks = Task::where('order_lines_id', $orderLineId)->get();

            foreach ($tasks as $task) {
                $task->update(['status_id' => $statusUpdate->id]);

                // Enregistrer une activité de fermeture
                $this->recordTaskActivity($task->id);

                // Déclencher un événement pour notifier le changement de statut
                Event::dispatch(new TaskChangeStatu($task->id));
            }
        }
    }

    public function recordTaskActivity($taskId)
    {
        TaskActivities::create([
            'task_id' => $taskId,
            'type' => '3',
            'timestamp' => Carbon::now(),
            'comment' => '',
        ]);
    }
}
