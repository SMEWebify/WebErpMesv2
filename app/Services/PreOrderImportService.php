<?php

namespace App\Services;

use App\Models\Workflow\PreOrder;
use App\Models\Workflow\PreOrderImport;
use Illuminate\Support\Carbon;

class PreOrderImportService
{
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
            $groupedRows[$sourcePdf][] = [
                'row_index' => $index + 1,
                'reference' => $row['reference'] ?? null,
                'product' => $row['product'] ?? null,
                'quantity' => $this->asDecimal($row['quantity'] ?? 0),
                'unit_price' => $this->asDecimal($row['unit_price'] ?? 0),
                'total_price' => $this->asDecimal($row['total_price'] ?? 0),
            ];
        }

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

        $rows = [];
        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count(array_filter($data, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $rows[] = array_combine($headers, array_pad($data, count($headers), null));
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

