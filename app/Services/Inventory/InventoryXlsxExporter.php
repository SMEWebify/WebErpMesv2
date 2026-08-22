<?php

namespace App\Services\Inventory;

use App\Models\Products\Inventory;
use App\Models\Products\InventoryDetail;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

/**
 * Renders the counting spreadsheet the operator will print or share.
 *
 * The layout matches the Altior-style workflow: one row per snapshot detail,
 * a Qté comptée column to fill in, and formulas that compute the variance
 * live so a supervisor can eyeball the diff before re-import.
 *
 * In "blind" mode we hide theoretical qty, reserved qty, unit cost and
 * variance columns so the counter is not biased by the expected value. The
 * data still exists in the file — hidden, not deleted — so re-import works
 * unchanged.
 */
class InventoryXlsxExporter
{
    // Column letters used across writer + validation. Kept as constants so
    // headers, formulas and hidden-column logic never drift.
    private const COL_ID              = 'A';
    private const COL_PRODUCT_CODE    = 'B';
    private const COL_PRODUCT_LABEL   = 'C';
    private const COL_ZONE            = 'D';
    private const COL_LOCATION        = 'E';
    private const COL_BATCH           = 'F';
    private const COL_SERIAL          = 'G';
    private const COL_QUALITY         = 'H';
    private const COL_THEORETICAL_QTY = 'I';
    private const COL_RESERVED_QTY    = 'J';
    private const COL_COUNTED_QTY     = 'K';
    private const COL_STATUS          = 'L';
    private const COL_UNIT_COST       = 'M';
    private const COL_VARIANCE_QTY    = 'N';
    private const COL_VARIANCE_VALUE  = 'O';
    private const COL_NOTES           = 'P';
    // Geometry columns: pushed to the right, Altior-style, so a copy-pasted
    // row (found offcut) can be given a different length/width without
    // touching the identifier columns.
    private const COL_X_SIZE          = 'Q';
    private const COL_Y_SIZE          = 'R';
    private const COL_Z_SIZE          = 'S';
    private const COL_NB_PART         = 'T';
    private const COL_SURFACE_PERC    = 'U';

    private const HEADER_ROW = 1;
    private const FIRST_DATA_ROW = 2;

    /**
     * Writes the XLSX to a temporary path and returns it. The caller is
     * responsible for streaming it as a download.
     */
    public function export(Inventory $inventory, bool $blind = false): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inventaire');

        $this->writeMetadata($spreadsheet, $inventory);
        $this->writeHeaders($sheet, $blind);

        $inventory->loadMissing([
            'details.product:id,code,label',
            'details.stockLocationProduct.StockLocation:id,code',
            'details.batch:id,number',
        ]);

        $row = self::FIRST_DATA_ROW;

        foreach ($inventory->details as $detail) {
            $this->writeDataRow($sheet, $row, $detail);
            $row++;
        }

        $lastRow = $row - 1;

        if ($lastRow >= self::FIRST_DATA_ROW) {
            $this->applyStatusValidation($sheet, $lastRow);
        }

        $this->autosizeColumns($sheet);

        if ($blind) {
            $this->hideRevealingColumns($sheet);
        }

        $path = $this->writeToTemp($spreadsheet, $inventory, $blind);

        // PhpSpreadsheet keeps a lot of memory around per Spreadsheet instance.
        // Explicit disconnect stops workers from growing unbounded.
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $path;
    }

    private function writeMetadata(Spreadsheet $spreadsheet, Inventory $inventory): void
    {
        $spreadsheet->getProperties()
            ->setCreator('WebErpMesv2')
            ->setTitle('Inventaire ' . $inventory->code)
            ->setDescription('Fichier de comptage inventaire ' . $inventory->code);
    }

    private function writeHeaders($sheet, bool $blind): void
    {
        $row = self::HEADER_ROW;

        $labels = [
            self::COL_ID              => '__id',
            self::COL_PRODUCT_CODE    => __('general_content.code_trans_key'),
            self::COL_PRODUCT_LABEL   => __('general_content.label_trans_key'),
            self::COL_ZONE            => __('general_content.zone_trans_key'),
            self::COL_LOCATION        => __('general_content.location_trans_key'),
            self::COL_BATCH           => __('general_content.batch_trans_key'),
            self::COL_SERIAL          => __('general_content.serial_number_trans_key'),
            self::COL_QUALITY         => __('general_content.quality_trans_key'),
            self::COL_THEORETICAL_QTY => __('general_content.theoretical_qty_trans_key'),
            self::COL_RESERVED_QTY    => __('general_content.reserved_qty_trans_key'),
            self::COL_COUNTED_QTY     => __('general_content.counted_qty_trans_key'),
            self::COL_STATUS          => __('general_content.status_trans_key'),
            self::COL_UNIT_COST       => __('general_content.unit_cost_trans_key'),
            self::COL_VARIANCE_QTY    => __('general_content.variance_qty_trans_key'),
            self::COL_VARIANCE_VALUE  => __('general_content.variance_value_trans_key'),
            self::COL_NOTES           => __('general_content.notes_trans_key'),
            self::COL_X_SIZE          => __('general_content.x_size_trans_key'),
            self::COL_Y_SIZE          => __('general_content.y_size_trans_key'),
            self::COL_Z_SIZE          => __('general_content.z_size_trans_key'),
            self::COL_NB_PART         => __('general_content.nb_part_trans_key'),
            self::COL_SURFACE_PERC    => __('general_content.surface_perc_trans_key'),
        ];

        foreach ($labels as $col => $label) {
            $sheet->setCellValue("{$col}{$row}", $label);
        }

        $range = self::COL_ID . $row . ':' . self::COL_SURFACE_PERC . $row;
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $blind ? 'B71C1C' : '1976D2'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->freezePane('A' . self::FIRST_DATA_ROW);
    }

    private function writeDataRow($sheet, int $row, InventoryDetail $detail): void
    {
        $sheet->setCellValue(self::COL_ID . $row, $detail->id);
        $sheet->setCellValue(self::COL_PRODUCT_CODE . $row, $detail->product?->code);
        $sheet->setCellValue(self::COL_PRODUCT_LABEL . $row, $detail->product?->label);
        $sheet->setCellValue(self::COL_ZONE . $row, $detail->stockLocationProduct?->StockLocation?->code);
        $sheet->setCellValue(self::COL_LOCATION . $row, $detail->stockLocationProduct?->code);
        $sheet->setCellValue(self::COL_BATCH . $row, $detail->batch?->number);
        $sheet->setCellValue(self::COL_SERIAL . $row, null);
        $sheet->setCellValue(self::COL_QUALITY . $row, $detail->quality);
        $sheet->setCellValue(self::COL_THEORETICAL_QTY . $row, (float) $detail->theoretical_qty);
        $sheet->setCellValue(self::COL_RESERVED_QTY . $row, (float) $detail->reserved_qty);
        $sheet->setCellValue(self::COL_COUNTED_QTY . $row, null);
        $sheet->setCellValue(self::COL_STATUS . $row, InventoryDetail::STATUS_PENDING);
        $sheet->setCellValue(self::COL_UNIT_COST . $row, (float) $detail->unit_cost);

        // Formulas resolve at spreadsheet open so the counter sees the diff
        // immediately without waiting for a re-import.
        $sheet->setCellValue(
            self::COL_VARIANCE_QTY . $row,
            '=IF(' . self::COL_COUNTED_QTY . $row . '="","",' . self::COL_COUNTED_QTY . $row . '-' . self::COL_THEORETICAL_QTY . $row . ')'
        );
        $sheet->setCellValue(
            self::COL_VARIANCE_VALUE . $row,
            '=IF(' . self::COL_COUNTED_QTY . $row . '="","",' . self::COL_VARIANCE_QTY . $row . '*' . self::COL_UNIT_COST . $row . ')'
        );

        $sheet->setCellValue(self::COL_NOTES . $row, $detail->notes);

        // Physical geometry — the counter identifies which sheet / offcut
        // they are looking at, and copies these values over when they add
        // a new row for a found offcut.
        $sheet->setCellValue(self::COL_X_SIZE . $row,       $detail->x_size !== null ? (float) $detail->x_size : null);
        $sheet->setCellValue(self::COL_Y_SIZE . $row,       $detail->y_size !== null ? (float) $detail->y_size : null);
        $sheet->setCellValue(self::COL_Z_SIZE . $row,       $detail->z_size !== null ? (float) $detail->z_size : null);
        $sheet->setCellValue(self::COL_NB_PART . $row,      $detail->nb_part !== null ? (int) $detail->nb_part : null);
        $sheet->setCellValue(self::COL_SURFACE_PERC . $row, $detail->surface_perc !== null ? (float) $detail->surface_perc : null);

        // Highlight the column the operator actually has to fill.
        $sheet->getStyle(self::COL_COUNTED_QTY . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('FFF9C4');
    }

    private function applyStatusValidation($sheet, int $lastRow): void
    {
        $range = self::COL_STATUS . self::FIRST_DATA_ROW . ':' . self::COL_STATUS . $lastRow;
        $validation = $sheet->getDataValidation($range);

        $validation->setType(DataValidation::TYPE_LIST)
            ->setErrorStyle(DataValidation::STYLE_INFORMATION)
            ->setAllowBlank(false)
            ->setShowInputMessage(true)
            ->setShowErrorMessage(true)
            ->setShowDropDown(true)
            ->setFormula1('"' . implode(',', [
                InventoryDetail::STATUS_PENDING,
                InventoryDetail::STATUS_VALIDATED,
                InventoryDetail::STATUS_TO_CHECK,
            ]) . '"');
    }

    private function autosizeColumns($sheet): void
    {
        foreach (range(self::COL_ID, self::COL_SURFACE_PERC) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function hideRevealingColumns($sheet): void
    {
        foreach ([
            self::COL_THEORETICAL_QTY,
            self::COL_RESERVED_QTY,
            self::COL_UNIT_COST,
            self::COL_VARIANCE_QTY,
            self::COL_VARIANCE_VALUE,
        ] as $col) {
            $sheet->getColumnDimension($col)->setVisible(false);
        }
    }

    private function writeToTemp(Spreadsheet $spreadsheet, Inventory $inventory, bool $blind): string
    {
        $suffix = $blind ? '-blind' : '';
        $filename = 'inventory-' . Str::slug($inventory->code) . $suffix . '-' . Str::random(6) . '.xlsx';
        $path = storage_path('app/temp/' . $filename);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        (new XlsxWriter($spreadsheet))->save($path);

        return $path;
    }
}
