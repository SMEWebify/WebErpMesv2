<?php

namespace App\Observers;

use App\Models\Planning\Task;
use App\Models\Workflow\OrderLines;
use App\Services\StockReservationService;

/**
 * Recalcule les réservations quand une OrderLines.qty change : la formule
 * de besoin est task.qty × orderline.qty, donc éditer la quantité commandée
 * doit propager sur toutes les tâches attachées à cette ligne.
 */
class OrderLinesObserver
{
    public function __construct(private readonly StockReservationService $reservations) {}

    public function updated(OrderLines $orderLine): void
    {
        if (!$orderLine->isDirty('qty')) {
            return;
        }

        $componentIds = Task::where('order_lines_id', $orderLine->id)
            ->whereNotNull('component_id')
            ->pluck('component_id')
            ->unique()
            ->all();

        foreach ($componentIds as $componentId) {
            $this->reservations->recomputeForProduct((int) $componentId);
        }
    }
}
