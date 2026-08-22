<?php

namespace App\Services\Inventory;

use App\Models\Products\Inventory;
use App\Models\Products\InventoryDetail;
use App\Models\Products\Products;
use App\Models\Products\StockLocationProducts;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Parses the Altior-style counting spreadsheet coming back from the operator
 * and reconciles it with the frozen inventory_details snapshot.
 *
 * Two entry points on purpose:
 *  - preview() : parses + validates + returns a diff, without touching the DB;
 *  - apply()   : same parsing, but writes counted_qty / status / notes.
 *
 * The preview flow lets the UI show errors and a summary card before the
 * user commits, matching the Altior behaviour where a failed import can be
 * corrected and re-uploaded.
 */
class InventoryXlsxImporter
{
    // Column positions must match InventoryXlsxExporter.
    private const COL_ID              = 1;  // A
    private const COL_PRODUCT_CODE    = 2;  // B
    private const COL_PRODUCT_LABEL   = 3;  // C
    private const COL_ZONE            = 4;  // D
    private const COL_LOCATION        = 5;  // E
    private const COL_BATCH           = 6;  // F
    private const COL_SERIAL          = 7;  // G
    private const COL_QUALITY         = 8;  // H
    private const COL_THEORETICAL_QTY = 9;  // I
    private const COL_RESERVED_QTY    = 10; // J
    private const COL_COUNTED_QTY     = 11; // K
    private const COL_STATUS          = 12; // L
    private const COL_UNIT_COST       = 13; // M
    private const COL_NOTES           = 16; // P
    private const COL_X_SIZE          = 17; // Q
    private const COL_Y_SIZE          = 18; // R
    private const COL_Z_SIZE          = 19; // S
    private const COL_NB_PART         = 20; // T
    private const COL_SURFACE_PERC    = 21; // U

    private const FIRST_DATA_ROW = 2;

    /**
     * Parse + validate without persisting.
     *
     * @return array{errors: array<int, array{row:int,message:string}>, rows: array<int, array>, summary: array}
     */
    public function preview(Inventory $inventory, UploadedFile $file): array
    {
        return $this->parse($inventory, $file);
    }

    /**
     * Parse + validate + persist the counted quantities. When the parse hits
     * any blocking error, nothing is written and the errors are returned to
     * the caller for display.
     *
     * @return array{errors: array<int, array{row:int,message:string}>, rows: array<int, array>, summary: array}
     */
    public function apply(Inventory $inventory, UploadedFile $file, int $userId): array
    {
        $result = $this->parse($inventory, $file);

        if ($result['errors'] !== []) {
            return $result;
        }

        DB::transaction(function () use ($inventory, $result, $userId) {
            $now = Carbon::now();

            foreach ($result['rows'] as $row) {
                if ($row['id'] !== null) {
                    InventoryDetail::where('id', $row['id'])
                        ->where('inventory_id', $inventory->id)
                        ->update([
                            'counted_qty' => $row['counted_qty'],
                            'status'      => $row['status'],
                            'notes'       => $row['notes'],
                            'quality'     => $row['quality'],
                            'counted_by'  => $userId,
                            'counted_at'  => $now,
                            'updated_at'  => $now,
                        ]);
                    continue;
                }

                // New row added by the counter (found offcut / batch that
                // was not in the snapshot). Geometry is carried over so a
                // 1000x1000 offcut of a sheet-metal product creates its own
                // physical item rather than merging into the parent line.
                InventoryDetail::create([
                    'inventory_id'               => $inventory->id,
                    'stock_location_products_id' => $row['stock_location_products_id'],
                    'products_id'                => $row['products_id'],
                    'batch_id'                   => null,
                    'serial_number_id'           => null,
                    'quality'                    => $row['quality'],
                    'x_size'                     => $row['x_size'],
                    'y_size'                     => $row['y_size'],
                    'z_size'                     => $row['z_size'],
                    'nb_part'                    => $row['nb_part'],
                    'surface_perc'               => $row['surface_perc'],
                    'theoretical_qty'            => 0,
                    'reserved_qty'               => 0,
                    'unit_cost'                  => $row['unit_cost'] ?? 0,
                    'counted_qty'                => $row['counted_qty'],
                    'counted_by'                 => $userId,
                    'counted_at'                 => $now,
                    'status'                     => $row['status'],
                    'notes'                      => $row['notes'],
                    'properties'                 => null,
                ]);
            }

            $inventory->forceFill([
                'statu' => Inventory::STATUS_EXPORTED,
            ])->save();
        });

        return $result;
    }

    /**
     * Core parser shared by preview() and apply().
     *
     * @return array{errors: array<int, array{row:int,message:string}>, rows: array<int, array>, summary: array}
     */
    private function parse(Inventory $inventory, UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();

        $errors  = [];
        $rows    = [];
        $summary = [
            'total_lines'                 => 0,
            'counted_lines'               => 0,
            'positive_variance_count'     => 0,
            'negative_variance_count'     => 0,
            'positive_variance_value'     => 0.0,
            'negative_variance_value'     => 0.0,
        ];

        // Pre-load the inventory details keyed by id, so an O(1) lookup replaces
        // per-row queries. Same for the SLP lookup used by new rows.
        $detailsById = $inventory->details()->get()->keyBy('id');

        for ($rowIdx = self::FIRST_DATA_ROW; $rowIdx <= $highestRow; $rowIdx++) {
            $idRaw          = $this->cell($sheet, self::COL_ID, $rowIdx);
            $productCode    = $this->cellString($sheet, self::COL_PRODUCT_CODE, $rowIdx);
            $locationCode   = $this->cellString($sheet, self::COL_LOCATION, $rowIdx);
            $countedRaw     = $this->cell($sheet, self::COL_COUNTED_QTY, $rowIdx);
            $status         = $this->cellString($sheet, self::COL_STATUS, $rowIdx) ?: InventoryDetail::STATUS_PENDING;
            $quality        = $this->cellString($sheet, self::COL_QUALITY, $rowIdx) ?: null;
            $notes          = $this->cellString($sheet, self::COL_NOTES, $rowIdx) ?: null;
            $unitCostRaw    = $this->cell($sheet, self::COL_UNIT_COST, $rowIdx);
            $xSize          = $this->parseFloatOrNull($this->cell($sheet, self::COL_X_SIZE, $rowIdx));
            $ySize          = $this->parseFloatOrNull($this->cell($sheet, self::COL_Y_SIZE, $rowIdx));
            $zSize          = $this->parseFloatOrNull($this->cell($sheet, self::COL_Z_SIZE, $rowIdx));
            $nbPart         = $this->parseIntOrNull($this->cell($sheet, self::COL_NB_PART, $rowIdx));
            $surfacePerc    = $this->parseFloatOrNull($this->cell($sheet, self::COL_SURFACE_PERC, $rowIdx));

            // A completely empty row after autosize sometimes lingers.
            if ($idRaw === null && $productCode === '' && $countedRaw === null) {
                continue;
            }

            $summary['total_lines']++;

            $detailId = $this->parseIntOrNull($idRaw);
            $countedQty = $this->parseFloatOrNull($countedRaw);
            $unitCost = $this->parseFloatOrNull($unitCostRaw);

            if (! in_array($status, [
                InventoryDetail::STATUS_PENDING,
                InventoryDetail::STATUS_VALIDATED,
                InventoryDetail::STATUS_TO_CHECK,
            ], true)) {
                $errors[] = ['row' => $rowIdx, 'message' => __('general_content.inventory_invalid_status_trans_key') . " ({$status})"];
                continue;
            }

            $detail = $detailId !== null ? $detailsById->get($detailId) : null;
            $productsId = null;
            $slpId = null;
            $reservedQty = 0.0;
            $theoreticalQty = 0.0;

            if ($detailId !== null && $detail === null) {
                $errors[] = ['row' => $rowIdx, 'message' => __('general_content.inventory_unknown_detail_id_trans_key') . " (#{$detailId})"];
                continue;
            }

            if ($detail !== null) {
                $productsId = $detail->products_id;
                $slpId = $detail->stock_location_products_id;
                $reservedQty = (float) $detail->reserved_qty;
                $theoreticalQty = (float) $detail->theoretical_qty;
                $unitCost = (float) $detail->unit_cost;
            } else {
                // Row added by the counter. Resolve product + location.
                if ($productCode === '' || $locationCode === '') {
                    $errors[] = ['row' => $rowIdx, 'message' => __('general_content.inventory_missing_product_or_location_trans_key')];
                    continue;
                }

                $product = Products::where('code', $productCode)->first(['id']);
                if ($product === null) {
                    $errors[] = ['row' => $rowIdx, 'message' => __('general_content.inventory_unknown_product_trans_key') . " ({$productCode})"];
                    continue;
                }

                $slp = StockLocationProducts::where('code', $locationCode)
                    ->where('products_id', $product->id)
                    ->first(['id']);

                if ($slp === null) {
                    $errors[] = ['row' => $rowIdx, 'message' => __('general_content.inventory_unknown_location_trans_key') . " ({$locationCode})"];
                    continue;
                }

                $productsId = $product->id;
                $slpId = $slp->id;
            }

            if ($countedQty !== null && $countedQty < $reservedQty) {
                $errors[] = [
                    'row' => $rowIdx,
                    'message' => __('general_content.inventory_counted_below_reserved_trans_key')
                        . " ({$countedQty} < {$reservedQty})",
                ];
                continue;
            }

            if ($countedQty !== null) {
                $summary['counted_lines']++;
                $variance = $countedQty - $theoreticalQty;
                if ($variance > 0) {
                    $summary['positive_variance_count']++;
                    $summary['positive_variance_value'] += round($variance * ($unitCost ?? 0), 2);
                } elseif ($variance < 0) {
                    $summary['negative_variance_count']++;
                    $summary['negative_variance_value'] += round($variance * ($unitCost ?? 0), 2);
                }
            }

            $rows[] = [
                'id'                         => $detailId,
                'row'                        => $rowIdx,
                'products_id'                => $productsId,
                'stock_location_products_id' => $slpId,
                'product_code'               => $productCode,
                'location_code'              => $locationCode,
                'quality'                    => $quality,
                'counted_qty'                => $countedQty,
                'status'                     => $status,
                'notes'                      => $notes,
                'unit_cost'                  => $unitCost,
                'reserved_qty'               => $reservedQty,
                'theoretical_qty'            => $theoreticalQty,
                'x_size'                     => $xSize,
                'y_size'                     => $ySize,
                'z_size'                     => $zSize,
                'nb_part'                    => $nbPart,
                'surface_perc'               => $surfacePerc,
            ];
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return [
            'errors'  => $errors,
            'rows'    => $rows,
            'summary' => $summary,
        ];
    }

    private function cell($sheet, int $col, int $row): mixed
    {
        return $sheet->getCellByColumnAndRow($col, $row)->getValue();
    }

    private function cellString($sheet, int $col, int $row): string
    {
        $val = $this->cell($sheet, $col, $row);
        return $val === null ? '' : trim((string) $val);
    }

    private function parseIntOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (! is_numeric($value)) {
            return null;
        }
        return (int) $value;
    }

    private function parseFloatOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (! is_numeric($value)) {
            return null;
        }
        return (float) $value;
    }
}
