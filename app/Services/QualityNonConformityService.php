<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;
use App\Models\Workflow\DeliveryLines;
use App\Models\Quality\QualityNonConformity;
use App\Notifications\NonConformityNotification;

class QualityNonConformityService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Create a new Quality Non-Conformity
     *
     * @param array $data
     * @return \App\Models\Quality\QualityNonConformity
     */
    public function createNonConformity(array $data)
    {
        // Create non-conformity
        $nonConformity = QualityNonConformity::create($data);

        // Set the type based on the query
        $nonConformity->type = isset($data['type']) ? 1 : 2;

       // Save changes
        $nonConformity->save();

        // Send notification
        $this->notificationService->sendNotification(NonConformityNotification::class, $nonConformity, 'non_conformity_notification');

        return $nonConformity;
    }

    /**
     * Create a new Quality Non-Conformity from a Delivery
     *
     * @param int $deliveryLineId
     * @return \App\Models\Quality\QualityNonConformity
     */
    public function createNCFromDelivery($deliveryLineId)
    {
        // Retrieve the delivery line
        $deliveryLine = DeliveryLines::findOrFail($deliveryLineId);

        // Create non-conformity
        $newNonConformity = QualityNonConformity::create([
            'code' => "NC-OR-#" . $deliveryLine->OrderLine->orders_id,
            'label' => "NC-L-#" . $deliveryLine->OrderLine->id,
            'statu' => 1,
            'type' => 2,
            'user_id' => $deliveryLine->delivery->user_id,
            'companie_id' => $deliveryLine->delivery->companies_id,
            'order_lines_id' => $deliveryLine->OrderLine->id,
            'deliverys_id' => $deliveryLine->deliverys_id,
            'delivery_line_id' => $deliveryLineId,
        ]);

        // Send notification
        $this->notificationService->sendNotification(NonConformityNotification::class, $newNonConformity, 'non_conformity_notification');

        return $newNonConformity;
    }

    /**
     * Create a new Quality Non-Conformity for an Order Line or Task
     *
     * @param int $id The ID of the order line or task
     * @param int $companieId
     * @param int|null $serviceId Optional service ID (for tasks)
     * @param string $type The type of non-conformity ('order_line' or 'task')
     * @return \App\Models\Quality\QualityNonConformity
     */
    public function  createNC($id, $companieId, $serviceId = null, $type = 'order_line')
    {
        $data = [
            'code' => $type === 'task' ? "NC-TASK-#{$id}" : "NC-OR-#{$id}",
            'label' => $type === 'task' ? "NC-TASK-#{$id}" : "NC-L-#{$id}",
            'statu' => 1,
            'type' => 1,
            'user_id' => Auth::id(),
            'companie_id' => $companieId,
        ];

        // Ajout des champs spécifiques aux tâches si applicable
        if ($type === 'task') {
            $data['methods_services_id'] = $serviceId;
            $data['task_id'] = $id;
        } else {
            $data['order_lines_id'] = $id;
        }

        // Create non-conformity
        $newNonConformity = QualityNonConformity::create($data);

        // Send notification
        $this->notificationService->sendNotification(NonConformityNotification::class, $newNonConformity, 'non_conformity_notification');

        return $newNonConformity;
    }
}
