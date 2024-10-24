<?php

namespace App\Services;

use App\Services\TaskService;
use App\Models\Workflow\InvoiceLines;
use App\Models\Accounting\AccountingAllocation;

class InvoiceLineService
{
    protected $taskService;
    protected $accountingEntryService;

    public function __construct(TaskService $taskService,AccountingEntryService $accountingEntryService)
    {
        $this->taskService = $taskService;
        $this->accountingEntryService = $accountingEntryService;
    }

    public function createInvoiceLine($invoiceCreated, $key, $deliveryId, $ordre, $qty , $VatID)
    {

        $allocationId = $this->accountingEntryService->getAllocationId(1, $VatID);

        // Créer la ligne de facturation
        $invoiceLines = InvoiceLines::create([
            'invoices_id' => $invoiceCreated->id,
            'order_line_id' => $key, 
            'delivery_line_id' => $deliveryId, 
            'ordre' => $ordre,
            'qty' => $qty,
            'accounting_allocation_id' => $allocationId,
            'statu' => 1
        ]); 

        if($allocationId != null){
            // Créer une entrée comptable pour cette ligne de facture
            $this->accountingEntryService->createSaleEntry($invoiceLines);
        }
        
        // Mettre à jour les tâches liées si facture direct
        if($deliveryId == null){
            $this->taskService->closeTasks($key);
        }

        return $invoiceLines;
    }
}
