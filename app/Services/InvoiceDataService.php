<?php

namespace App\Services;

use App\Models\Workflow\DeliveryLines;
use Illuminate\Support\Collection;

class InvoiceDataService
{
    /**
     * Get unique company IDs from delivery lines with specific invoice statuses.
     *
     * @return Collection
     */
    public function getUniqueCompanyIdsWithOpenInvoiceLines(): Collection
    {
        return DeliveryLines::whereIn('delivery_lines.invoice_status', ['1', '2'])
                                        ->leftJoin('deliverys', 'delivery_lines.deliverys_id', '=', 'deliverys.id')
                                        ->pluck('deliverys.companies_id')
                                        ->filter()
                                        ->unique()
                                        ->map(fn($id) => (int)$id)
                                        ->values();
    }

    /**
     * Get invoice request lines filtered by company ID and sorted by provided parameters.
     *
     * @param int|null $companyId
     * @param string|null $dateStart
     * @param string|null $dateEnd
     * @param string $sortField
     * @param bool $sortAsc
     * @return Collection
     */
    public function getInvoiceRequestsLines(
        ?int $companyId,
        ?string $dateStart = null,
        ?string $dateEnd = null,
        string $sortField = 'id',
        bool $sortAsc = true
    ): Collection
    {
        return DeliveryLines::orderBy($sortField, $sortAsc ? 'asc' : 'desc')
            ->whereIn('invoice_status', ['1', '2'])
            ->whereHas('delivery', function ($q) use ($companyId, $dateStart, $dateEnd) {
                if (!empty($companyId)) {
                    $q->where('companies_id', '=', (int)$companyId);
                }
                if (!empty($dateStart)) {
                    $q->whereDate('created_at', '>=', $dateStart);
                }
                if (!empty($dateEnd)) {
                    $q->whereDate('created_at', '<=', $dateEnd);
                }
            })->get();
    }
}
