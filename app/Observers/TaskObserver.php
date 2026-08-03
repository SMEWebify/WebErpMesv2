<?php

namespace App\Observers;

use App\Models\Planning\Task;
use App\Services\StockReservationService;

class TaskObserver
{
    public function __construct(private readonly StockReservationService $reservations) {}

    public function created(Task $task): void
    {
        if ($task->component_id) {
            $this->reservations->recomputeForProduct((int) $task->component_id);
        }
    }

    public function updated(Task $task): void
    {
        $watched = ['component_id', 'qty', 'end_date', 'status_id'];
        if (!$task->isDirty($watched)) {
            return;
        }

        // Si component_id a changé, il faut relibérer l'ancien composant.
        if ($task->isDirty('component_id')) {
            $oldComponentId = $task->getOriginal('component_id');
            if ($oldComponentId) {
                $this->reservations->recomputeForProduct((int) $oldComponentId);
            }
        }

        if ($task->component_id) {
            $this->reservations->recomputeForProduct((int) $task->component_id);
        }
    }

    public function deleted(Task $task): void
    {
        // Fire aussi sur soft-delete : la tâche supprimée libère sa réservation.
        if ($task->component_id) {
            $this->reservations->recomputeForProduct((int) $task->component_id);
        }
    }
}
