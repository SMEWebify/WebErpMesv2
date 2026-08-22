<?php

namespace App\Services\Inventory;

use App\Models\Products\Inventory;
use App\Models\Products\InventoryDetail;
use App\Models\Products\StockLocationProducts;
use App\Models\Products\StockMove;
use App\Services\StockService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    // Aligned on StockValuationService / StockReservationService.
    // typ_move=15 (Inventory shortage) is included on the OUTGOING side so
    // regularisation moves are always accounted for by the same SQL logic.
    private const INCOMING = [1, 3, 5, 12, 14];
    private const OUTGOING = [2, 4, 6, 9, 15];

    public function __construct(private readonly StockService $stockService)
    {
    }

    /**
     * Create the inventory header and take a snapshot of the current stock
     * for every stock_location_products entry inside the requested scope.
     *
     * @param  array{label?: string, scope_type?: string, scope_ids?: array<int, int>, code?: string}  $data
     */
    public function create(array $data, int $userId): Inventory
    {
        return DB::transaction(function () use ($data, $userId) {
            $scopeType = $data['scope_type'] ?? Inventory::SCOPE_ALL;
            $scopeIds  = $scopeType === Inventory::SCOPE_ALL ? null : array_values($data['scope_ids'] ?? []);

            $inventory = Inventory::create([
                'code'         => $data['code'] ?? $this->generateCode(),
                'label'        => $data['label'] ?? __('general_content.inventory_default_label_trans_key') . ' ' . now()->format('d/m/Y'),
                'scope_type'   => $scopeType,
                'scope_ids'    => $scopeIds,
                'start_date'   => Carbon::now()->toDateString(),
                'frozen_at'    => Carbon::now(),
                'created_by'   => $userId,
                'statu'        => Inventory::STATUS_DRAFT,
            ]);

            $this->snapshotDetails($inventory);

            return $inventory->fresh('details');
        });
    }

    /**
     * Cancel an inventory: no stock move is generated, the counting file
     * (if any) stays attached for audit but the header becomes read-only.
     */
    public function cancel(Inventory $inventory): void
    {
        if ($inventory->isLocked()) {
            throw new \DomainException(__('general_content.inventory_already_locked_trans_key'));
        }

        $inventory->forceFill([
            'statu' => Inventory::STATUS_CANCELLED,
        ])->save();
    }

    /**
     * Finalise the inventory: generate one stock_move per detail whose counted
     * quantity differs from the theoretical one, then lock the header.
     *
     * The lock is pessimistic on every affected stock_location_products row so
     * two concurrent inventories cannot double-count the same bin.
     */
    public function validate(Inventory $inventory, int $userId): void
    {
        if ($inventory->isLocked()) {
            throw new \DomainException(__('general_content.inventory_already_locked_trans_key'));
        }

        DB::transaction(function () use ($inventory, $userId) {
            $slpIds = $inventory->details()->pluck('stock_location_products_id')->unique()->all();

            // Pessimistic lock so a task pick-up or a transfer cannot slip
            // between our recount and the write of the regularisation moves.
            StockLocationProducts::whereIn('id', $slpIds)->lockForUpdate()->get();

            $stockChanges = $this->detectStockChanges($inventory);
            if ($stockChanges !== []) {
                throw new \DomainException(
                    __('general_content.inventory_stock_changed_since_snapshot_trans_key')
                    . ' — ' . implode(', ', array_slice($stockChanges, 0, 5))
                );
            }

            $details = $inventory->details()->whereNotNull('counted_qty')->get();

            foreach ($details as $detail) {
                $variance = (float) $detail->counted_qty - (float) $detail->theoretical_qty;

                if ($variance == 0.0) {
                    continue;
                }

                $this->stockService->createStockMove([
                    'user_id'                    => $userId,
                    'qty'                        => (int) round(abs($variance)),
                    'stock_location_products_id' => $detail->stock_location_products_id,
                    'batch_id'                   => $detail->batch_id,
                    'inventory_id'               => $inventory->id,
                    'typ_move'                   => $variance > 0
                        ? 1   // Inventory entry (INCOMING)
                        : 15, // Inventory shortage (OUTGOING)
                    'component_price'            => (float) $detail->unit_cost,
                    // Preserve the physical geometry so a sheet-metal offcut
                    // stays identifiable in downstream reservations / nesting.
                    'x_size'                     => $detail->x_size,
                    'y_size'                     => $detail->y_size,
                    'z_size'                     => $detail->z_size,
                    'nb_part'                    => $detail->nb_part,
                    'surface_perc'               => $detail->surface_perc,
                ]);
            }

            $inventory->forceFill([
                'statu'        => Inventory::STATUS_VALIDATED,
                'validated_at' => Carbon::now(),
                'validated_by' => $userId,
                'end_date'     => Carbon::now()->toDateString(),
            ])->save();
        });
    }

    /**
     * Compare the frozen theoretical_qty against the live stock for every
     * (slp, batch) pair, so we can refuse validation when the physical stock
     * moved between the snapshot and the count.
     *
     * @return array<int, string> list of human-readable product references
     */
    public function detectStockChanges(Inventory $inventory): array
    {
        $mismatches = [];

        $entryList   = implode(',', self::INCOMING);
        $outgoingList = implode(',', self::OUTGOING);

        $details = $inventory->details()->with('product:id,code')->get();

        foreach ($details as $detail) {
            $query = StockMove::where('stock_location_products_id', $detail->stock_location_products_id)
                ->selectRaw("
                    COALESCE(SUM(CASE
                        WHEN typ_move IN ($entryList)   THEN qty
                        WHEN typ_move IN ($outgoingList) THEN -qty
                        ELSE 0
                    END), 0) AS net_qty
                ");

            $this->applyGeometryFilter($query, $detail);

            $currentQty = (float) $query->value('net_qty');

            if (abs($currentQty - (float) $detail->theoretical_qty) > 0.0001) {
                $mismatches[] = $detail->product?->code ?? "SLP#{$detail->stock_location_products_id}";
            }
        }

        return $mismatches;
    }

    /**
     * Narrow a stock_moves query to a specific (batch, geometry) tuple so a
     * sheet-metal offcut is not accidentally reconciled against the full
     * sheet snapshot. Null values are matched with IS NULL rather than
     * equality so bins without geometry keep working unchanged.
     */
    private function applyGeometryFilter($query, InventoryDetail $detail): void
    {
        foreach ([
            'batch_id'     => $detail->batch_id,
            'x_size'       => $detail->x_size,
            'y_size'       => $detail->y_size,
            'z_size'       => $detail->z_size,
            'nb_part'      => $detail->nb_part,
            'surface_perc' => $detail->surface_perc,
        ] as $col => $val) {
            if ($val === null) {
                $query->whereNull($col);
            } else {
                $query->where($col, $val);
            }
        }
    }

    /**
     * Snapshot the current stock into inventory_details rows, one per
     * (stock_location_products, batch) pair with a non-zero net quantity.
     * Empty bins are still snapshotted (theoretical_qty=0) so the counter
     * can validate that they are indeed empty.
     */
    private function snapshotDetails(Inventory $inventory): void
    {
        $slpIds = $this->resolveScopeSlpIds($inventory);

        if ($slpIds === []) {
            return;
        }

        $entryList    = implode(',', self::INCOMING);
        $outgoingList = implode(',', self::OUTGOING);

        // Grouping by geometry keeps each physical item as its own row:
        // a full 1500x3000 sheet and a 1000x1000 offcut on the same product
        // never fuse into a single line the counter cannot disambiguate.
        $rows = DB::table('stock_location_products as slp')
            ->leftJoin('stock_moves as sm', 'sm.stock_location_products_id', '=', 'slp.id')
            ->whereIn('slp.id', $slpIds)
            ->groupBy(
                'slp.id',
                'slp.products_id',
                'slp.unit_cost',
                'sm.batch_id',
                'sm.x_size',
                'sm.y_size',
                'sm.z_size',
                'sm.nb_part',
                'sm.surface_perc',
            )
            ->selectRaw("
                slp.id            as slp_id,
                slp.products_id   as products_id,
                slp.unit_cost     as unit_cost,
                sm.batch_id       as batch_id,
                sm.x_size         as x_size,
                sm.y_size         as y_size,
                sm.z_size         as z_size,
                sm.nb_part        as nb_part,
                sm.surface_perc   as surface_perc,
                COALESCE(SUM(CASE
                    WHEN sm.typ_move IN ($entryList)   THEN sm.qty
                    WHEN sm.typ_move IN ($outgoingList) THEN -sm.qty
                    ELSE 0
                END), 0) as qty
            ")
            ->get();

        $reservations = $this->reservationsBySlp($slpIds);

        $now = Carbon::now();
        $inserts = [];

        foreach ($rows as $row) {
            // A bin with no moves at all shows as one row with qty=0, batch=null.
            // We still keep it so the counter can confirm "yes, it is empty".
            $inserts[] = [
                'inventory_id'               => $inventory->id,
                'stock_location_products_id' => $row->slp_id,
                'products_id'                => $row->products_id,
                'batch_id'                   => $row->batch_id,
                'serial_number_id'           => null,
                'quality'                    => null,
                'x_size'                     => $row->x_size,
                'y_size'                     => $row->y_size,
                'z_size'                     => $row->z_size,
                'nb_part'                    => $row->nb_part,
                'surface_perc'               => $row->surface_perc,
                'theoretical_qty'            => (float) $row->qty,
                'reserved_qty'               => (float) ($reservations[$row->slp_id] ?? 0),
                'unit_cost'                  => (float) $row->unit_cost,
                'counted_qty'                => null,
                'counted_by'                 => null,
                'counted_at'                 => null,
                'status'                     => InventoryDetail::STATUS_PENDING,
                'notes'                      => null,
                'properties'                 => null,
                'created_at'                 => $now,
                'updated_at'                 => $now,
            ];
        }

        // Chunked insert to keep memory bounded on large stock bases.
        foreach (array_chunk($inserts, 500) as $chunk) {
            InventoryDetail::insert($chunk);
        }
    }

    /**
     * Resolve the list of stock_location_products IDs that fall in scope.
     *
     * @return array<int, int>
     */
    private function resolveScopeSlpIds(Inventory $inventory): array
    {
        return match ($inventory->scope_type) {
            Inventory::SCOPE_LOCATION => StockLocationProducts::query()
                ->whereIn('stock_locations_id', $inventory->scope_ids ?? [])
                ->pluck('id')
                ->all(),

            Inventory::SCOPE_CATEGORY => StockLocationProducts::query()
                ->whereIn('products_id', function ($q) use ($inventory) {
                    $q->select('id')
                        ->from('products')
                        ->whereIn('methods_families_id', $inventory->scope_ids ?? []);
                })
                ->pluck('id')
                ->all(),

            default => StockLocationProducts::query()->pluck('id')->all(),
        };
    }

    /**
     * Total reserved quantity per stock_location_products, taken from the
     * stock_reservations table. The counter needs this so they never enter
     * a counted quantity below the reserved amount.
     *
     * @param  array<int, int>  $slpIds
     * @return array<int, float>
     */
    private function reservationsBySlp(array $slpIds): array
    {
        if ($slpIds === []) {
            return [];
        }

        $rows = DB::table('stock_reservations as sr')
            ->join('stock_location_products as slp', 'slp.products_id', '=', 'sr.products_id')
            ->whereIn('slp.id', $slpIds)
            ->where('sr.status', 'active')
            ->groupBy('slp.id')
            ->selectRaw('slp.id as slp_id, COALESCE(SUM(sr.qty_reserved), 0) as reserved')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row->slp_id] = (float) $row->reserved;
        }

        return $out;
    }

    private function generateCode(): string
    {
        $prefix = 'INV-' . now()->format('Ymd');
        $suffix = 1;

        while (Inventory::where('code', "{$prefix}-{$suffix}")->exists()) {
            $suffix++;
        }

        return "{$prefix}-{$suffix}";
    }
}
