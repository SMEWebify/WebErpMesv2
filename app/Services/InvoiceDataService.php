<?php

namespace App\Services;

use App\Models\Workflow\DeliveryLines;
use App\Models\Workflow\InvoiceLines;
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
        // 1 = Facturable, 3 = Partiellement. Exclut 2 (non facturable) et 4 (facturé).
        return DeliveryLines::whereIn('delivery_lines.invoice_status', ['1', '3'])
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
            ->whereIn('invoice_status', ['1', '3']) // facturable + partiel (exclut non facturable / facturé)
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

    /**
     * Sérialise une ligne de facture pour le composant React du brouillon.
     *
     * Source unique pour la vue (rendu initial) et pour l'API (ligne ajoutée),
     * afin que les deux ne divergent pas. Tolère les lignes libres, qui n'ont
     * ni commande ni bon de livraison en face.
     */
    public function formatDraftLine(InvoiceLines $line): array
    {
        $order    = $line->orderLine?->order;
        $delivery = $line->delivery_line_id ? $line->deliveryLine?->delivery : null;

        return [
            'id'               => $line->id,
            'qty'              => (float) $line->qty,
            'unit_price'       => $line->resolved_unit_price,
            'discount'         => $line->resolved_discount,
            'vat_rate'         => $line->resolved_vat_rate,
            'invoice_status'   => $line->invoice_status,
            'is_free_line'     => $line->is_free_line,
            'order_line_code'  => $line->display_code,
            'order_line_label' => $line->display_label,
            'unit_label'       => $line->display_unit_label,
            'order_code'       => $order?->code,
            'order_url'        => $order ? route('orders.show', $order->id) : null,
            'delivery_code'    => $delivery?->code,
            'delivery_url'     => $delivery ? route('deliverys.show', ['id' => $delivery->id]) : null,
        ];
    }
}
