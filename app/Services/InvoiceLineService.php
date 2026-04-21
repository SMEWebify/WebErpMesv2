<?php

namespace App\Services;

use App\Services\TaskService;
use App\Models\Workflow\InvoiceLines;
use App\Models\Workflow\OrderLines;

class InvoiceLineService
{
    protected $taskService;
    protected $accountingEntryService;

    public function __construct(TaskService $taskService,AccountingEntryService $accountingEntryService)
    {
        $this->taskService = $taskService;
        $this->accountingEntryService = $accountingEntryService;
    }

    /**
     * Create an invoice line and associated accounting entry.
     *
     * This method creates a new invoice line with the provided details and 
     * generates an associated accounting entry if the allocation ID is not null.
     * If the delivery ID is null, it updates related tasks as well.
     *
     * @param object $invoiceCreated The created invoice object.
     * @param int $key The order line ID.
     * @param int|null $deliveryId The delivery line ID, or null if not applicable.
     * @param int $ordre The order of the invoice line.
     * @param float $qty The quantity for the invoice line.
     * @param int $VatID The VAT ID for the invoice line.
     * @return \App\Models\Workflow\InvoiceLines The created invoice line.
     */
    public function createInvoiceLine($invoiceCreated, $key, $deliveryId, $ordre, $qty , $VatID)
    {

        $allocationId = $this->accountingEntryService->getAllocationId(1, $VatID);

        $orderLine = OrderLines::with('VAT')->find($key);

        $invoiceLines = InvoiceLines::create([
            'invoices_id'              => $invoiceCreated->id,
            'order_line_id'            => $key,
            'delivery_line_id'         => $deliveryId,
            'ordre'                    => $ordre,
            'qty'                      => $qty,
            'unit_price'               => $orderLine->selling_price,
            'discount'                 => $orderLine->discount,
            'vat_rate'                 => $orderLine->VAT?->rate ?? 0,
            'accounting_allocation_id' => $allocationId,
            'statu'                    => 1,
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
