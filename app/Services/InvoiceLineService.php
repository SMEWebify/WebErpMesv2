<?php

namespace App\Services;

use App\Services\TaskService;
use App\Models\Workflow\InvoiceLines;

class InvoiceLineService
{
    protected $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    public function createInvoiceLine($invoiceCreated, $key, $deliveryId, $ordre, $qty)
    {
        // Créer la ligne de facturation
        $invoiceLines = InvoiceLines::create([
            'invoices_id' => $invoiceCreated->id,
            'order_line_id' => $key, 
            'delivery_line_id' => $deliveryId, 
            'ordre' => $ordre,
            'qty' => $qty,
            'statu' => 1
        ]); 

        // Mettre à jour les tâches liées si facture direct
        if($deliveryId == null){
            $this->taskService->closeTasks($key);
        }

        return $invoiceLines;
    }
}
