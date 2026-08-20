<?php

namespace App\Services;

use App\Services\TaskService;
use App\Models\Workflow\InvoiceLines;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\OrderLines;
use App\Models\Accounting\AccountingVat;
use App\Models\Methods\MethodsUnits;

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
            'accounting_vats_id'       => $orderLine->accounting_vats_id,
            'accounting_allocation_id' => $allocationId,
            'invoice_status'           => 1,
        ]);

        if ($allocationId != null && $invoiceCreated->invoice_type === 1) {
            $this->accountingEntryService->createSaleEntry($invoiceLines);
        }
        
        if ($deliveryId == null && $invoiceCreated->invoice_type === 1) {
            $this->taskService->closeTasks($key);
        }

        return $invoiceLines;
    }

    /**
     * Crée une ligne de facture libre, sans ligne de commande d'origine.
     *
     * Répond au cas des prestations facturées après coup — frais de port, frais
     * de dossier, prestation ponctuelle — pour lesquelles il n'existe ni ligne
     * de commande ni bon de livraison. La ligne porte elle-même sa désignation,
     * son unité et sa TVA ; le snapshot de prix est donc complet dès la création.
     *
     * @param  \App\Models\Workflow\Invoices  $invoice  Facture (en brouillon) qui reçoit la ligne.
     * @param  array  $data  label, code, qty, unit_price, discount, accounting_vats_id, methods_units_id, product_id.
     * @return \App\Models\Workflow\InvoiceLines
     */
    public function createFreeLine(Invoices $invoice, array $data): InvoiceLines
    {
        $vat  = isset($data['accounting_vats_id'])
            ? AccountingVat::find($data['accounting_vats_id'])
            : AccountingVat::getDefault();

        $unit = isset($data['methods_units_id'])
            ? MethodsUnits::find($data['methods_units_id'])
            : MethodsUnits::getDefault();

        $allocationId = $vat ? $this->accountingEntryService->getAllocationId(1, $vat->id) : null;

        // La ligne se place en fin de facture.
        $ordre = (int) InvoiceLines::where('invoices_id', $invoice->id)->max('ordre') + 10;

        $invoiceLine = InvoiceLines::create([
            'invoices_id'              => $invoice->id,
            'order_line_id'            => null,
            'delivery_line_id'         => null,
            'label'                    => $data['label'],
            'code'                     => $data['code'] ?? null,
            'product_id'               => $data['product_id'] ?? null,
            'methods_units_id'         => $unit?->id,
            'ordre'                    => $ordre,
            'qty'                      => $data['qty'],
            'unit_price'               => $data['unit_price'],
            'discount'                 => $data['discount'] ?? 0,
            'vat_rate'                 => $vat?->rate ?? 0,
            'accounting_vats_id'       => $vat?->id,
            'accounting_allocation_id' => $allocationId,
            'invoice_status'           => $invoice->statu,
        ]);

        if ($allocationId !== null && $invoice->invoice_type === 1) {
            $this->accountingEntryService->createSaleEntry($invoiceLine);
        }

        return $invoiceLine;
    }
}
