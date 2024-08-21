<?php

namespace App\Services;

use App\Services\TaskService;
use App\Models\workflow\DeliveryLines;

class DeliveryLineService
{
    protected $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    public function createDeliveryLine($deliveryCreated, $key, $ordre, $qty)
    {
        // Créer la ligne de livraison
        $deliveryLine = DeliveryLines::create([
            'deliverys_id' => $deliveryCreated->id,
            'order_line_id' => $key,
            'ordre' => $ordre,
            'qty' => $qty,
            'statu' => 1
        ]);

        // Mettre à jour les tâches liées
        $this->taskService->closeTasks($key);

        return $deliveryLine;
    }
}
