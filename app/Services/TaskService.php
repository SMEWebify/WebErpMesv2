<?php

namespace App\Services;

use App\Events\TaskActivityTriggered;
use Carbon\Carbon;
use App\Models\Planning\Task;
use App\Events\TaskChangeStatu;
use App\Models\Planning\Status;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use App\Models\Planning\TaskActivities;
use App\Models\User;

class TaskService
{
    /**
     * Close tasks associated with a specific order line.
     *
     * This function retrieves the status ID for the "Finished" status and updates
     * the status of all tasks associated with the given order line ID to "Finished".
     * It also records a task activity for each task and dispatches an event to notify
     * about the status change.
     *
     * @param int $orderLineId The ID of the order line whose tasks need to be closed.
     * @return void
     */
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
                $this->recordTaskActivity($task->id, TaskActivities::TYPE_FINISH, 0, 0);

                // Déclencher un événement pour notifier le changement de statut
                Event::dispatch(new TaskChangeStatu($task->id));
            }
        }
    }

    /**
     * Records a task activity and broadcasts an event.
     *
     * @param int $taskId The ID of the task.
     * @param string $type The type of activity.
     * @param int $goodQty The quantity of good items.
     * @param int $addBadQt The quantity of bad items.
     * @return void
     */
    public function recordTaskActivity($taskId, $type, $goodQty = 0, $addBadQt = 0, string $comment = '')
    {
        $userId = Auth::id();

        if (!$userId) {
            $userId = Task::find($taskId)?->user_id;
        }

        if (!$userId) {
            $userId = User::query()->value('id');
        }

        if (!$userId) {
            return;
        }

        $taskActivity = TaskActivities::create([
            'task_id' => $taskId,
            'user_id'=> $userId,
            'type' => $type,
            'timestamp' => Carbon::now(),
            'good_qt'=> $goodQty,
            'bad_qt'=> $addBadQt,
            'comment' => $comment,
        ]);

        broadcast(new TaskActivityTriggered($taskActivity));
    }
}
