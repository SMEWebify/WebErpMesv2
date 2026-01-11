<?php

namespace App\Services;

use App\Models\Workflow\OrderLines;
use Illuminate\Support\Collection;

class DeliveryDataService
{
    /**
     * Get unique company IDs from order lines with specific delivery statuses.
     *
     * @return Collection
     */
    public function getUniqueCompanyIdsWithOpenOrderLines(): Collection
    {
        return OrderLines::whereIn('delivery_status', ['1', '2'])
            ->leftJoin('orders', 'order_lines.orders_id', '=', 'orders.id')
            ->whereNotIn('orders.statu', [5, 6])
            ->where('orders.type', '=', '1')
            ->pluck('orders.companies_id')
            ->filter()
            ->unique()
            ->map(fn($id) => (int)$id)
            ->values();
    }

    /**
     * Get delivery request lines filtered by company ID and sorted by provided parameters.
     *
     * @param int|null $companyId
     * @param string $sortField
     * @param bool $sortAsc
     * @return Collection
     */
    public function getDeliveryRequestsLines(?int $companyId, string $sortField = 'id', bool $sortAsc = true): Collection
    {
        return OrderLines::orderBy($sortField, $sortAsc ? 'asc' : 'desc')
            ->whereIn('delivery_status', ['1', '2'])
            ->whereHas('order', function ($q) use ($companyId) {
                if (!empty($companyId)) {
                    $q->where('companies_id', '=', (int)$companyId);
                }
                $q->whereNotIn('statu', [5, 6])
                    ->where('type', '=', '1');
            })->get();
    }
}
