<?php

namespace App\Services;

use App\Models\Planning\Task;
use App\Models\Planning\Status;
use App\Models\Purchases\Purchases;
use Illuminate\Support\Facades\Auth;
use App\Models\Purchases\PurchaseLines;
use App\Models\Accounting\AccountingVat;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Purchases\PurchaseQuotationLines;

class PurchaseOrderService
{
    protected $documentCodeGenerator ;

    public function __construct(DocumentCodeGenerator $documentCodeGeneratorService , ){
        $this->documentCodeGenerator  = $documentCodeGeneratorService ;
    }

    /**
     * Create a new purchase order.
     *
     * This method creates a new purchase order with the provided data.
     *
     * @param int $purchaseData The ID of the company making the purchase.
     * @param string $purchaseCode The unique code for the purchase order.
     * @param string $purchaseLabel The label for the purchase order.
     * @param int $defaultContact The ID of the default contact for the purchase order.
     * @param int $defaultAddress The ID of the default address for the purchase order.
     * @return \App\Models\Purchases\Purchases The created purchase order.
     */
    public function createPurchaseOrder($purchaseData, $purchaseCode, $purchaseLabel, $defaultContact, $defaultAddress)
    {
        return Purchases::create([
            'code' => $purchaseCode,
            'label' => $purchaseLabel,
            'companies_id' => $purchaseData,
            'companies_contacts_id' => $defaultContact,
            'companies_addresses_id' => $defaultAddress,
            'user_id' => Auth::id(),
        ]);
    }

    /**
     * Create a new purchase order from a quotation.
     *
     * This method creates a new purchase order based on the provided quotation data.
     *
     * @param $PurchasesQuotationData The data from the purchase quotation.
     * @return \App\Models\Purchases\Purchases|bool The created purchase order or false if defaults are missing.
     */
    public function createPurchaseOrderFromQuotation($PurchasesQuotationData)
    {
        // Generate a unique purchase code
        $purchaseCode = $this->generatePurchaseCode();

        // Get default address and contact for the company
        $defaultAddress = CompaniesAddresses::getDefault(['companies_id' => $PurchasesQuotationData->companies_id]);
        $defaultContact = CompaniesContacts::getDefault(['companies_id' => $PurchasesQuotationData->companies_id]);
        $accountingVat = $this->getAccountingVat();

        // Check if defaults are available
        if (!$defaultAddress || !$defaultContact || !$accountingVat) {
            return false;
        }

        // Create and return the purchase order
        return $this->createPurchaseOrder($PurchasesQuotationData->companies_id, $purchaseCode, $purchaseCode, $defaultContact->id, $defaultAddress->id);
    }

    /**
     * Create a new purchase order line.
     *
     * This method creates a new purchase order line with the provided data.
     *
     * @param int $purchaseOrder The ID of the purchase order.
     * @param \App\Models\Planning\Task $task The task associated with the purchase order line.
     * @param int $accountingVat The ID of the accounting VAT.
     * @param int $ordre The order of the line.
     * @param float $purchasePrice The purchase Price applied to the line.
     * @param float $discount The discount applied to the line.
     * @return \App\Models\Purchases\PurchaseLines The created purchase order line.
     */
    public function createPurchaseOrderLine($purchaseOrder, $task, $accountingVat, $ordre , $purchasePrice = null, $discount = 0)
    {
        $finalPrice = (!empty($purchasePrice)) ? $purchasePrice : $task->unit_cost;
        return PurchaseLines::create([
            'purchases_id' => $purchaseOrder,
            'tasks_id' => $task->id,
            'ordre' => $ordre,
            'code' => $task->code,
            'product_id' => $task->component_id,
            'label' => $task->label,
            //'supplier_ref' => , can be null
            'qty' => $task->getQualityRequiredAttribute(),
            'selling_price' => $finalPrice,
            'discount' => $discount,
            'unit_price_after_discount' => $finalPrice,
            'total_selling_price' => $finalPrice * $task->getQualityRequiredAttribute(),
            //'receipt_qty' =>, defaut to 0
            //'invoiced_qty' =>, defaut to 0
            'methods_units_id' => $task->methods_units_id,
            'accounting_vats_id' => $accountingVat,
            //'stock_locations_id' => , can be null
            'statu' => 1,
        ]);
    }

    /**
     * Create a new purchase order line from a quotation line.
     *
     * This method creates a purchase order line based on a generic quotation line.
     *
     * @param int $purchaseOrder The ID of the purchase order.
     * @param \App\Models\Purchases\PurchaseQuotationLines $quotationLine The quotation line to convert.
     * @param int $accountingVat The ID of the accounting VAT.
     * @param int $ordre The order of the line.
     * @param float|null $purchasePrice The purchase price applied to the line.
     * @return \App\Models\Purchases\PurchaseLines The created purchase order line.
     */
    public function createPurchaseOrderLineFromQuotationLine($purchaseOrder, $quotationLine, $accountingVat, $ordre, $purchasePrice = null)
    {
        $finalPrice = (!empty($purchasePrice)) ? $purchasePrice : $quotationLine->unit_price;
        return PurchaseLines::create([
            'purchases_id' => $purchaseOrder,
            'tasks_id' => 0,
            'ordre' => $ordre,
            'code' => $quotationLine->code,
            'product_id' => $quotationLine->product_id,
            'label' => $quotationLine->label ?? 'Generic line',
            'qty' => $quotationLine->qty_to_order,
            'selling_price' => $finalPrice,
            'discount' => 0,
            'unit_price_after_discount' => $finalPrice,
            'total_selling_price' => $finalPrice * $quotationLine->qty_to_order,
            'methods_units_id' => null,
            'accounting_vats_id' => $accountingVat,
            'statu' => 1,
        ]);
    }

    /**
     * Get the default accounting VAT.
     *
     * This method retrieves the default accounting VAT from the database.
     *
     * @return \App\Models\Accounting\AccountingVat The default accounting VAT.
     */
    public function getAccountingVat()
    {
        return AccountingVat::getDefault();
    }

    /**
     * Update the status of a task.
     *
     * This method updates the status of a task with the given task ID.
     *
     * @param int $taskId The ID of the task to update.
     * @param int $statusId The ID of the new status.
     * @return int The number of affected rows.
     */
    public function updateTaskStatus($taskId, $statusId)
    {
        return Task::where('id', $taskId)->update(['status_id' => $statusId]);
    }

    /**
     * Update the accepted quantity of a quotation line.
     *
     * This method updates the accepted quantity of a purchase quotation line with the given line ID.
     *
     * @param int $lineId The ID of the quotation line to update.
     * @param int $qtyAccepted The accepted quantity to set.
     * @return int The number of affected rows.
     */
    public function updateQuotationLine($lineId, $qtyAccepted)
    {
        return PurchaseQuotationLines::where('id', $lineId)->update(['qty_accepted' => $qtyAccepted]);
    }

    /**
     * Get the status update.
     *
     * This method retrieves the status update for 'Supplied' or 'In progress'.
     *
     * @return \App\Models\Planning\Status The status update.
     */
    public function getStatusUpdate()
    {
        return Status::whereIn('title', ['Supplied', 'In progress'])->orderBy('id')->first();
    }

    /**
     * Process purchase quotation lines.
     *
     * This method processes purchase quotation lines and updates the task status and quotation line.
     *
     * @param array $quotationLines The quotation lines to process.
     * @param array $taskIds The task IDs associated with the quotation lines.
     * @param \App\Models\Purchases\Purchases $purchaseOrder The purchase order.
     * @param int $statusUpdateId The ID of the status update.
     */
    public function processPurchaseQuotationLines($quotationLines, $taskIds, $purchaseOrder, $statusUpdateId, $prices)
    {
        $accountingVat = $this->getAccountingVat();
        $ordre = 10;
        foreach ($quotationLines as $key => $line) {
            $taskId = $taskIds[$key] ?? null;
            $price = $prices[$key] ?? null;
            $quotationLine = PurchaseQuotationLines::find($line);
            $task = $taskId ? Task::find($taskId) : null;

            if ($task) {
                $this->createPurchaseOrderLine($purchaseOrder->id, $task, $accountingVat->id, $ordre, $price);
                $this->updateTaskStatus($task->id, $statusUpdateId);
                $this->updateQuotationLine($line, $task->getQualityRequiredAttribute());
            } elseif ($quotationLine) {
                $this->createPurchaseOrderLineFromQuotationLine($purchaseOrder->id, $quotationLine, $accountingVat->id, $ordre, $price);
                $this->updateQuotationLine($line, $quotationLine->qty_to_order);
            }
            $ordre += 10;
        }
    }

    /**
     * Process purchase request lines.
     *
     * This method processes purchase request lines and updates the task status.
     *
     * @param array $purchaseRequestLine The purchase request lines to process.
     * @param \App\Models\Purchases\Purchases $purchaseOrder The purchase order.
     * @param int $statusUpdateId The ID of the status update.
     */
    public function processPurchaseRequestLines($purchaseRequestLine, $purchaseOrder, $statusUpdateId)
    {
        $accountingVat = $this->getAccountingVat();
        $ordre = 10;
        foreach ($purchaseRequestLine as $key => $line) {
            $task = Task::find($key);
            $this->createPurchaseOrderLine($purchaseOrder->id, $task, $accountingVat->id, $ordre);
            $this->updateTaskStatus($task->id, $statusUpdateId);
            $ordre += 10;
        }
    }

    /**
     * Generate a unique purchase code.
     *
     * This method generates a unique purchase code based on the last purchase ID.
     *
     * @return string The generated purchase code.
     */
    public function generatePurchaseCode()
    {
        $lastPurchase = Purchases::orderBy('id', 'desc')->first();
        $purchaseId = $lastPurchase ? $lastPurchase->id : 0;
        return $this->documentCodeGenerator->generateDocumentCode('purchase', $purchaseId);
    }
}
