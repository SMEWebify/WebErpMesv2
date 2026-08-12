<?php

namespace App\Listeners;

use App\Events\TaskChangeStatu;
use App\Models\Planning\Status;
use App\Models\Planning\Task;
use App\Models\Workflow\OrderLines;
use Illuminate\Support\Facades\Cache;

class CheckOrderLineTaskStatus
{
    /**
     * Recalcule order_lines.tasks_status en fonction de l'état des tâches.
     *
     *   1 = No task     — aucune tâche attachée
     *   2 = Created     — au moins une tâche, mais aucune démarrée
     *   3 = In progress — au moins une tâche démarrée ou terminée
     *   4 = Finished    — toutes les tâches terminées
     *
     * Volontairement synchrone : opération légère (3 counts + 1 update) et
     * critique pour l'UI (badge dans /orders/{id}/show). Un worker absent en
     * dev ne doit pas laisser la ligne bloquée sur un statut périmé.
     */
    public function handle(TaskChangeStatu $event): void
    {
        $task = Task::find($event->taskId);
        if (! $task || ! $task->order_lines_id) {
            return;
        }

        self::syncOrderLine((int) $task->order_lines_id);
    }

    /**
     * Point d'entrée réutilisable, appelable après une suppression de tâche
     * (où l'event TaskChangeStatu n'a pas de sens : plus de tâche à référencer).
     */
    public static function syncOrderLine(int $orderLineId): void
    {
        $finishedStatusId = Cache::rememberForever(
            'status_finished_id',
            fn () => Status::where('title', 'Finished')->value('id'),
        );

        $totalCount = Task::where('order_lines_id', $orderLineId)->count();

        if ($totalCount === 0) {
            $newStatus = 1;
        } else {
            $finishedCount = Task::where('order_lines_id', $orderLineId)
                ->where('status_id', $finishedStatusId)
                ->count();

            if ($finishedCount === $totalCount) {
                $newStatus = 4;
            } else {
                // Seul le premier statut de la table (order = min) est
                // considéré "non démarré". Tout le reste vaut "En cours".
                $initialStatusId = Cache::rememberForever(
                    'status_initial_id',
                    fn () => Status::orderBy('order')->value('id'),
                );

                $startedCount = Task::where('order_lines_id', $orderLineId)
                    ->where('status_id', '<>', $initialStatusId)
                    ->count();

                $newStatus = $startedCount > 0 ? 3 : 2;
            }
        }

        OrderLines::find($orderLineId)?->update(['tasks_status' => $newStatus]);
    }
}
