<?php

namespace App\Services;

use App\Services\TaskService;
use App\Models\Workflow\DeliveryLines;
use App\Models\Workflow\OrderLines;

class DeliveryLineService
{
    protected $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * Create a delivery line and update related tasks.
     *
     * This method creates a new delivery line with the provided details and updates
     * the status of related tasks by closing them.
     *
     * @param $deliveryCreated The created delivery object.
     * @param int $key The order line ID.
     * @param int $ordre The order number.
     * @param int $qty The quantity for the delivery line.
     * @return \App\Models\Workflow\DeliveryLines The created delivery line object.
     */
    public function createDeliveryLine($deliveryCreated, $key, $ordre, $qty)
    {
        $deliveryLine = DeliveryLines::create([
            'deliverys_id' => $deliveryCreated->id,
            'order_line_id' => $key,
            'ordre' => $ordre,
            'qty' => $qty,
            'ind' => OrderLines::whereKey($key)->value('ind'),
            'statu' => 1
        ]);

        $this->taskService->closeTasks($key);

        return $deliveryLine;
    }
}
