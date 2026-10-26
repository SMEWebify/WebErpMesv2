<?php

namespace App\Services;


use Illuminate\Support\Collection;
use App\Models\Purchases\PurchaseReceiptLines;

class PurchaseInvoiceService
{
    protected $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * Get purchase lines that are waiting for receipt.
     *
     * This function retrieves purchase lines that have not yet been fully received.
     * It allows sorting by a specified field and direction, and optionally filters by company ID.
     *
     * @param int|null $companies_id The ID of the company to filter by (optional).
     * @param string $sortField The field to sort by (default is 'id').
     * @param bool $sortAsc Whether to sort in ascending order (default is true).
     * @return \Illuminate\Database\Eloquent\Collection The collection of purchase lines waiting for receipt.
     */
    public function getPurchasesWaintingInvoiceLines($companies_id = null, $sortField = 'id', $sortAsc = true)
    {
        return PurchaseReceiptLines::orderBy($sortField, $sortAsc ? 'asc' : 'desc')
                                    ->whereHas('purchaseLines', function($query)use ($companies_id) {
                                        $query->whereColumn('invoiced_qty', '<', 'qty')->whereHas('purchase', function($q)
                                        use ($companies_id){
                                            // Égalité : le like '%1%' ramenait aussi les fournisseurs 10, 11…
                                            $q->when($companies_id, fn ($q) => $q->where('companies_id', $companies_id));
                                        }); // Comparer receipt_qty avec qty
                                    })
                                    ->get();
    }

    /**
     * Get unique company IDs from order lines with specific delivery statuses.
     *
     * @return Collection
     */
    public function getUniqueCompanyIdsWithOpenPurchaseReceiptLines(): Collection
    {
        return PurchaseReceiptLines::leftJoin('purchase_lines', 'purchase_receipt_lines.purchase_line_id', '=', 'purchase_lines.id')
                                    ->leftJoin('purchases', 'purchase_lines.purchases_id', '=', 'purchases.id')
                                    ->whereColumn('purchase_lines.receipt_qty', '<=', 'purchase_lines.qty')
                                    ->pluck('purchases.companies_id')
                                    ->filter()
                                    ->unique()
                                    ->map(fn($id) => (int)$id)
                                    ->values();
    }
}
