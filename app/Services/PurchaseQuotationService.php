<?php

namespace App\Services;

use App\Models\Planning\Task;
use App\Models\Planning\Status;
use App\Models\Companies\Companies;
use Illuminate\Support\Facades\Auth;
use App\Services\DocumentCodeGenerator;
use App\Models\Purchases\PurchasesQuotation;
use App\Models\Purchases\PurchaseQuotationLines;
use App\Models\Purchases\PurchaseRfqGroup;

class PurchaseQuotationService
{
    protected $documentCodeGenerator ;

    public function __construct(DocumentCodeGenerator $documentCodeGeneratorService , ){
        $this->documentCodeGenerator  = $documentCodeGeneratorService ;
    }
    
    /**
     * Create a new purchase quotation.
     *
     * This method creates a new purchase quotation with the provided data.
     *
     * @param int $purchaseQuotationData The ID of the company making the purchase quotation.
     * @param string $purchaseQuotationCode The unique code for the purchase quotation.
     * @param string $purchaseQuotationLabel The label for the purchase quotation.
     * @param int $defaultContact The ID of the default contact for the purchase quotation.
     * @param int $defaultAddress The ID of the default address for the purchase quotation.
     * @param int|null $rfqGroupId The ID of the RFQ group.
     * @return \App\Models\Purchases\PurchasesQuotation The created purchase quotation.
     */
    public function createPurchasesQuotation($purchaseQuotationData, $purchaseQuotationCode, $purchaseQuotationLabel, $defaultContact, $defaultAddress, $rfqGroupId = null)
    {
        return PurchasesQuotation::create([
            'code' => $purchaseQuotationCode,
            'label' => $purchaseQuotationLabel,
            'companies_id' => $purchaseQuotationData,
            'companies_contacts_id' => $defaultContact,
            'companies_addresses_id' => $defaultAddress,
            'rfq_group_id' => $rfqGroupId,
            'user_id' => Auth::id(),
        ]);
    }

    /**
     * Create a new RFQ group.
     *
     * @param string $code
     * @param string $label
     * @param string|null $description
     * @return \App\Models\Purchases\PurchaseRfqGroup
     */
    public function createRfqGroup($code, $label, $description = null)
    {
        return PurchaseRfqGroup::create([
            'code' => $code,
            'label' => $label,
            'description' => $description,
            'user_id' => Auth::id(),
        ]);
    }

    /**
     * Generate a unique quotation code for a supplier in a RFQ group.
     *
     * @param string $baseCode
     * @param \App\Models\Companies\Companies $company
     * @return string
     */
    public function generateGroupedQuotationCode($baseCode, Companies $company)
    {
        $suffix = $company->code ?: $company->id;
        $code = $baseCode . '-' . $suffix;

        if (PurchasesQuotation::where('code', $code)->exists()) {
            $code = $baseCode . '-' . $company->id . '-' . now()->format('His');
        }

        return $code;
    }

    /**
     * Create a new purchase quotation line.
     *
     * This method creates a new purchase quotation line with the provided data.
     *
     * @param \App\Models\Purchases\PurchasesQuotation $purchaseQuotation The purchase quotation.
     * @param \App\Models\Planning\Task $task The task associated with the purchase quotation line.
     * @param int $ordre The order of the line.
     * @return \App\Models\Purchases\PurchaseQuotationLines The created purchase quotation line.
     */
    public function createPurchaseQuotationLine($purchaseQuotation, $task,  $ordre)
    {
        return PurchaseQuotationLines::create([
            'purchases_quotation_id' => $purchaseQuotation->id,
            'tasks_id' => $task->id,
            'code' => $task->Component?->code ?? $task->code,
            'product_id' => $task->component_id,
            'label' => $task->label,
            'ordre' => $ordre, 
            //'supplier_ref' => , can be null
            'qty_to_order' => $task->getQualityRequiredAttribute(),
            'unit_price' => $task->unit_cost,
            'total_price' => $task->unit_cost * $task->getQualityRequiredAttribute(),
            //'qty_accepted' =>, defaut to 0
            //'canceled_qty' =>, defaut to 0
        ]); 
    }

    /**
     * Process purchase request lines.
     *
     * This method processes purchase request lines and updates the task status.
     *
     * @param array $purchaseRequestLine The purchase request lines to process.
     * @param \App\Models\Purchases\PurchasesQuotation $purchaseQuotation The purchase quotation.
     * @param int $statusUpdateId The ID of the status update.
     */
    public function processPurchaseRequestLines($purchaseRequestLine, $purchaseQuotation, $statusUpdateId)
    {
        $ordre = 10;
        foreach ($purchaseRequestLine as $key => $line) {
            $task = Task::find($key);
            $this->createPurchaseQuotationLine($purchaseQuotation, $task,  $ordre);
            $this->updateTaskStatus($task->id, $statusUpdateId);
            $ordre += 10;
        }
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
     * Get the status update.
     *
     * This method retrieves the status update for 'RFQ in progress' or 'Started'.
     *
     * @return \App\Models\Planning\Status The status update.
     */
    public function getStatusUpdate()
    {
        return Status::whereIn('title', ['RFQ in progress', 'Started'])->orderBy('id')->first();
    }

    /**
     * Generate a unique purchase quotation code.
     *
     * This method generates a unique purchase quotation code based on the last purchase quotation ID.
     *
     * @return string The generated purchase quotation code.
     */
    public function generatePurchasesQuotationCode()
    {
        $lastPurchasesQuotation = PurchasesQuotation::orderBy('id', 'desc')->first();
        $purchasesQuotationId = $lastPurchasesQuotation ? $lastPurchasesQuotation->id : 0;
        return $this->documentCodeGenerator->generateDocumentCode('purchase-quotation', $purchasesQuotationId);
    }
}
