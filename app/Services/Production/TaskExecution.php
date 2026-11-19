<?php

namespace App\Services\Production;

use App\Events\TaskChangeStatu;
use App\Models\Planning\Status;
use App\Models\Planning\Task;
use App\Models\Planning\TaskActivities;
use App\Services\Inspection\TaskInspection;
use App\Services\TaskService;

/**
 * Démarrage / fin d'une tâche et déclaration de quantités : mêmes écritures que
 * l'écran tâche (TaskStatuController), pour l'écran de scan (ScanStation).
 * Le contrôle du bon pour fabrication reste à l'appelant (ProductionRelease).
 */
class TaskExecution
{
    public function __construct(private TaskService $taskService)
    {
    }

    public function start(Task $task, ?int $userId = null): void
    {
        $this->taskService->recordTaskActivity($task->getKey(), TaskActivities::TYPE_START, 0, 0, '', $userId);

        $status = Status::where('title', 'In progress')->first()
               ?? Status::where('title', 'In Progress')->first();
        $this->setStatus($task, $status?->id);

        // Dossier de contrôle créé au démarrage quand l'article a un plan validé.
        // Un échec ici ne doit jamais empêcher de démarrer la production.
        try {
            app(TaskInspection::class)->dossierFor(Task::with('OrderLines.Product', 'OrderLines.order')->find($task->getKey()), create: true);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function finish(Task $task, ?int $userId = null): void
    {
        $this->taskService->recordTaskActivity($task->getKey(), TaskActivities::TYPE_FINISH, 0, 0, '', $userId);

        $this->setStatus($task, Status::where('title', 'Finished')->value('id'));
    }

    public function declareGood(Task $task, int $qty, ?int $userId = null): void
    {
        $this->taskService->recordTaskActivity($task->getKey(), TaskActivities::TYPE_DECLARE_GOOD, $qty, 0, '', $userId);
    }

    public function declareBad(Task $task, int $qty, ?int $userId = null): void
    {
        $this->taskService->recordTaskActivity($task->getKey(), TaskActivities::TYPE_DECLARE_BAD, 0, $qty, '', $userId);
    }

    private function setStatus(Task $task, ?int $statusId): void
    {
        if (! $statusId) {
            return;
        }

        Task::where('id', $task->getKey())->update(['status_id' => $statusId]);
        event(new TaskChangeStatu($task->getKey()));
    }
}
