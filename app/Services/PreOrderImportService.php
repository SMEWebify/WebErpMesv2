<?php

namespace App\Services;

use App\Models\Workflow\PreOrder;
use App\Models\Workflow\PreOrderImport;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PreOrderImportService
{
    private const REQUIRED_HEADERS = [
        'reference',
        'product',
        'quantity',
        'unit_price',
        'total_price',
        'source_pdf',
    ];

    public function importCsvFile(string $filePath): bool
    {
        if (!is_file($filePath)) {
            return false;
        }

        $checksum = sha1_file($filePath);
        if (PreOrderImport::where('checksum', $checksum)->exists()) {
            return false;
        }

        $rows = $this->readRows($filePath);
        if (empty($rows)) {
            return false;
        }

        $groupedRows = [];
        foreach ($rows as $index => $row) {
            $sourcePdf = trim((string) ($row['source_pdf'] ?? 'unknown.pdf'));
            $quantity = $this->asDecimal($row['quantity'] ?? 0);
            $unitPrice = $this->asDecimal($row['unit_price'] ?? 0);
            $totalPrice = $this->asDecimal($row['total_price'] ?? 0);

            if ($totalPrice <= 0 && $quantity > 0 && $unitPrice > 0) {
                $totalPrice = $quantity * $unitPrice;
            }

            $groupedRows[$sourcePdf][] = [
                'row_index' => $index + 1,
                'reference' => $row['reference'] ?? null,
                'product' => $row['product'] ?? null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
            ];
        }

        DB::transaction(function () use ($filePath, $checksum, $groupedRows) {
            $import = PreOrderImport::create([
                'file_name' => basename($filePath),
                'checksum' => $checksum,
                'imported_at' => Carbon::now(),
            ]);

            foreach ($groupedRows as $sourcePdf => $lines) {
                $preOrder = $import->preOrders()->create([
                    'source_pdf' => $sourcePdf,
                    'status' => PreOrder::STATUS_PENDING,
                ]);

                $preOrder->lines()->createMany($lines);
            }
        });

        return true;
    }

    private function readRows(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return [];
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return [];
        }

        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        rewind($handle);

        $headers = fgetcsv($handle, 0, $delimiter);
        if (!$headers) {
            fclose($handle);
            return [];
        }

        $headers = array_map(function ($header) {
            return strtolower(trim((string) $header));
        }, $headers);

        foreach (self::REQUIRED_HEADERS as $requiredHeader) {
            if (!in_array($requiredHeader, $headers, true)) {
                fclose($handle);
                return [];
            }
        }

        $rows = [];
        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count(array_filter($data, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $row = array_combine($headers, array_pad($data, count($headers), null));
            if ($row === false) {
                continue;
            }

            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function asDecimal($value): float
    {
        $normalized = str_replace(',', '.', (string) $value);
        return (float) $normalized;
    }
}
