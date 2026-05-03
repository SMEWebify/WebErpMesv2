<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Products\Products;
use App\Models\Products\StockLocationProducts;

class ProductMergeService
{
    /**
     * Return a preview of what will happen when merging duplicate into master.
     */
    public function preview(int $masterId, int $duplicateId): array
    {
        $master    = Products::findOrFail($masterId);
        $duplicate = Products::findOrFail($duplicateId);

        return [
            'master'    => $this->productSummary($master),
            'duplicate' => $this->productSummary($duplicate),
            'impact'    => [
                'quote_lines'              => DB::table('quote_lines')->where('product_id', $duplicateId)->count(),
                'order_lines'              => DB::table('order_lines')->where('product_id', $duplicateId)->count(),
                'purchase_lines'           => DB::table('purchase_lines')->where('product_id', $duplicateId)->count(),
                'purchase_quotation_lines' => DB::table('purchase_quotation_lines')->where('product_id', $duplicateId)->count(),
                'credit_note_lines'        => DB::table('credit_note_lines')->where('product_id', $duplicateId)->count(),
                'tasks'                    => DB::table('tasks')->where('products_id', $duplicateId)->count(),
                'sub_assemblies'           => DB::table('sub_assemblies')->where('products_id', $duplicateId)->count(),
                'serial_numbers'           => DB::table('serial_numbers')->where('products_id', $duplicateId)->count(),
                'batches'                  => DB::table('batches')->where('product_id', $duplicateId)->count(),
                'quality_amdecs'           => DB::table('quality_amdecs')->where('product_id', $duplicateId)->count(),
                'pre_order_lines'          => DB::table('pre_order_lines')
                                                ->where('suggested_product_id', $duplicateId)
                                                ->orWhere('linked_product_id', $duplicateId)
                                                ->count(),
                'stock'                    => $this->buildStockPreview($masterId, $duplicateId),
                'customer_price_lists'     => [
                    'master_count'    => DB::table('customer_price_lists')->where('products_id', $masterId)->count(),
                    'duplicate_count' => DB::table('customer_price_lists')->where('products_id', $duplicateId)->count(),
                ],
                'quantity_prices'          => [
                    'master_count'    => DB::table('products_quantity_prices')->where('products_id', $masterId)->count(),
                    'duplicate_count' => DB::table('products_quantity_prices')->where('products_id', $duplicateId)->count(),
                ],
            ],
        ];
    }

    /**
     * Merge duplicate into master inside a single transaction.
     * All FK references are moved to master, then the duplicate is soft-deleted.
     */
    public function merge(int $masterId, int $duplicateId): void
    {
        DB::transaction(function () use ($masterId, $duplicateId) {

            // 1. Simple FK reassignments
            DB::table('quote_lines')->where('product_id', $duplicateId)->update(['product_id' => $masterId]);
            DB::table('order_lines')->where('product_id', $duplicateId)->update(['product_id' => $masterId]);
            DB::table('purchase_lines')->where('product_id', $duplicateId)->update(['product_id' => $masterId]);
            DB::table('purchase_quotation_lines')->where('product_id', $duplicateId)->update(['product_id' => $masterId]);
            DB::table('credit_note_lines')->where('product_id', $duplicateId)->update(['product_id' => $masterId]);
            DB::table('tasks')->where('products_id', $duplicateId)->update(['products_id' => $masterId]);
            DB::table('tasks')->where('component_id', $duplicateId)->update(['component_id' => $masterId]);
            DB::table('sub_assemblies')->where('products_id', $duplicateId)->update(['products_id' => $masterId]);
            DB::table('serial_numbers')->where('products_id', $duplicateId)->update(['products_id' => $masterId]);
            DB::table('batches')->where('product_id', $duplicateId)->update(['product_id' => $masterId]);
            DB::table('quality_amdecs')->where('product_id', $duplicateId)->update(['product_id' => $masterId]);
            DB::table('pre_order_lines')->where('suggested_product_id', $duplicateId)->update(['suggested_product_id' => $masterId]);
            DB::table('pre_order_lines')->where('linked_product_id', $duplicateId)->update(['linked_product_id' => $masterId]);

            // 2. Pivot: preferred suppliers — add those not already on master, discard overlaps
            $masterSupplierIds = DB::table('products_preferred_suppliers')
                ->where('product_id', $masterId)->pluck('companies_id');
            DB::table('products_preferred_suppliers')
                ->where('product_id', $duplicateId)
                ->whereNotIn('companies_id', $masterSupplierIds)
                ->update(['product_id' => $masterId]);
            DB::table('products_preferred_suppliers')->where('product_id', $duplicateId)->delete();

            // 3. Pivot: tools — same pattern
            $masterToolIds = DB::table('product_tool')
                ->where('product_id', $masterId)->pluck('methods_tools_id');
            DB::table('product_tool')
                ->where('product_id', $duplicateId)
                ->whereNotIn('methods_tools_id', $masterToolIds)
                ->update(['product_id' => $masterId]);
            DB::table('product_tool')->where('product_id', $duplicateId)->delete();

            // 4. Stock — merge quantities by reassigning moves to master's stock location product
            $this->mergeStock($masterId, $duplicateId);

            // 5. Customer price lists — keep master's, discard duplicate's
            DB::table('customer_price_lists')->where('products_id', $duplicateId)->delete();

            // 6. Quantity prices — keep master's, discard duplicate's
            DB::table('products_quantity_prices')->where('products_id', $duplicateId)->delete();

            // 7. Files (polymorphic many-to-many via fileables pivot)
            $masterFileIds = DB::table('fileables')
                ->where('fileable_type', 'App\\Models\\Products\\Products')
                ->where('fileable_id', $masterId)
                ->pluck('file_id');
            DB::table('fileables')
                ->where('fileable_type', 'App\\Models\\Products\\Products')
                ->where('fileable_id', $duplicateId)
                ->whereNotIn('file_id', $masterFileIds)
                ->update(['fileable_id' => $masterId]);
            DB::table('fileables')
                ->where('fileable_type', 'App\\Models\\Products\\Products')
                ->where('fileable_id', $duplicateId)
                ->delete();

            // 8. Soft-delete the duplicate
            DB::table('products')
                ->where('id', $duplicateId)
                ->update(['deleted_at' => now()]);

            // 9. Audit trail
            activity()
                ->performedOn(Products::find($masterId))
                ->withProperties(['merged_from_id' => $duplicateId])
                ->log("Fusion produit #{$duplicateId} → #{$masterId}");
        });
    }

    // -------------------------------------------------------------------------

    private function mergeStock(int $masterId, int $duplicateId): void
    {
        $duplicateStocks = StockLocationProducts::where('products_id', $duplicateId)->get();

        foreach ($duplicateStocks as $dupStock) {
            $masterStock = StockLocationProducts::where('products_id', $masterId)
                ->where('stock_locations_id', $dupStock->stock_locations_id)
                ->first();

            if ($masterStock) {
                // Same location on both products: reassign all moves so quantities sum automatically
                DB::table('stock_moves')
                    ->where('stock_location_products_id', $dupStock->id)
                    ->update(['stock_location_products_id' => $masterStock->id]);
                DB::table('purchase_receipt_lines')
                    ->where('stock_location_products_id', $dupStock->id)
                    ->update(['stock_location_products_id' => $masterStock->id]);
                $dupStock->delete();
            } else {
                // No location conflict: simply repoint to master
                $dupStock->update(['products_id' => $masterId]);
            }
        }
    }

    private function buildStockPreview(int $masterId, int $duplicateId): array
    {
        $duplicateStocks = StockLocationProducts::where('products_id', $duplicateId)->get();
        $result = [];

        foreach ($duplicateStocks as $dupStock) {
            $masterStock = StockLocationProducts::where('products_id', $masterId)
                ->where('stock_locations_id', $dupStock->stock_locations_id)
                ->first();

            $dupQty    = $dupStock->getCurrentStockMove();
            $masterQty = $masterStock ? $masterStock->getCurrentStockMove() : 0;

            $result[] = [
                'location_id' => $dupStock->stock_locations_id,
                'dup_qty'     => $dupQty,
                'master_qty'  => $masterQty,
                'merged_qty'  => $dupQty + $masterQty,
                'conflict'    => $masterStock !== null,
            ];
        }

        return $result;
    }

    private function productSummary(Products $product): array
    {
        return [
            'id'    => $product->id,
            'code'  => $product->code,
            'label' => $product->label,
        ];
    }
}
