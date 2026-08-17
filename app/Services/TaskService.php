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
use App\Models\Planning\TaskResources;
use Illuminate\Support\Facades\DB;
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
     * $userIdOverride et $timestampOverride sont utilisés par les events entrants
     * de l'intégration (pas d'utilisateur Auth, horodatage source à préserver).
     */
    public function recordTaskActivity(
        $taskId,
        $type,
        $goodQty = 0,
        $addBadQt = 0,
        string $comment = '',
        ?int $userIdOverride = null,
        ?Carbon $timestampOverride = null,
    ) {
        $userId = $userIdOverride ?? Auth::id();

        if (!$userId) {
            $userId = Task::find($taskId)?->user_id;
        }

        if (!$userId) {
            $userId = User::query()->value('id');
        }

        if (!$userId) {
            return;
        }

        // Un timestamp source dans le futur (skew d'horloge N2P ou replay
        // malicieux) fausserait les fenêtres OEE. On borne à now().
        $now = Carbon::now();
        $timestamp = $timestampOverride && $timestampOverride->lessThan($now)
            ? $timestampOverride
            : $now;

        $taskActivity = TaskActivities::create([
            'task_id' => $taskId,
            'methods_ressources_id' => $this->resolveResourceId($taskId),
            'user_id'=> $userId,
            'type' => $type,
            'timestamp' => $timestamp,
            'good_qt'=> $goodQty,
            'bad_qt'=> $addBadQt,
            'comment' => $comment,
        ]);

        broadcast(new TaskActivityTriggered($taskActivity));
    }

    /**
     * Ressource à créditer du temps déclaré : la machine affectée, ou la
     * main-d'œuvre quand la tâche est un poste manuel. Null si la tâche n'est
     * pas affectée — la déclaration reste valide, simplement non imputée.
     *
     * Requête directe sur le pivot : recordTaskActivity est appelée en boucle
     * (fermeture d'une ligne de commande), on évite d'hydrater les relations.
     */
    private function resolveResourceId($taskId): ?int
    {
        $rows = DB::table('task_resources')
            ->where('task_id', $taskId)
            ->get(['methods_ressources_id', 'role']);

        $row = $rows->firstWhere('role', TaskResources::ROLE_MACHINE) ?? $rows->first();

        return $row?->methods_ressources_id;
    }
}
