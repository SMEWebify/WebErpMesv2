<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use RuntimeException;

class InvoiceReportInterpreter
{
    private const REPORT_FILENAME = 'processing_report.csv';

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getReport(?string $filename = null, bool $onlyToday = true): Collection
    {
        $rows = $this->readRows();

        return $this->filterRows($rows, $filename, $onlyToday)->values();
    }

    public function hasFailures(?string $filename = null, bool $onlyToday = true): bool
    {
        return $this->getReport($filename, $onlyToday)
            ->contains(static fn (array $row): bool => $row['is_error'] === true);
    }

    private function getReportPath(): string
    {
        // Only strip surrounding whitespace and trailing separators — never the
        // leading "/" of an absolute POSIX path, otherwise toAbsolutePath() would
        // misread it as relative and prepend base_path() (doubling the path).
        $configuredOutputPath = rtrim(trim((string) config('pre_orders.output_path', '')), "/\\");

        if ($configuredOutputPath === '') {
            return storage_path('app/python/output/' . self::REPORT_FILENAME);
        }

        if (str_ends_with($configuredOutputPath, '.csv')) {
            return $this->toAbsolutePath($configuredOutputPath);
        }

        return rtrim($this->toAbsolutePath($configuredOutputPath), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . self::REPORT_FILENAME;
    }

    private function toAbsolutePath(string $path): string
    {
        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:\\\\/', $path) === 1) {
            return $path;
        }

        return base_path($path);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function readRows(): Collection
    {
        $reportPath = $this->getReportPath();

        if (!is_file($reportPath) || !is_readable($reportPath)) {
            throw new RuntimeException(sprintf('Invoice processing report file is missing or not readable: %s', $reportPath));
        }

        $handle = fopen($reportPath, 'rb');

        if ($handle === false) {
            throw new RuntimeException(sprintf('Unable to open invoice processing report file: %s', $reportPath));
        }

        try {
            $header = fgetcsv($handle);

            if ($header === false) {
                return collect();
            }

            $normalizedHeader = array_map(static fn ($column): string => trim((string) $column), $header);

            $rows = collect();

            while (($row = fgetcsv($handle)) !== false) {
                if ($row === [null] || $row === []) {
                    continue;
                }

                $mappedRow = $this->mapRow($normalizedHeader, $row);

                if ($mappedRow === null) {
                    continue;
                }

                $rows->push($mappedRow);
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param array<int, string> $header
     * @param array<int, string|null> $row
     *
     * @return array<string, mixed>|null
     */
    private function mapRow(array $header, array $row): ?array
    {
        $combined = array_combine($header, array_pad($row, count($header), null));

        if ($combined === false) {
            return null;
        }

        $status = trim((string) ($combined['status'] ?? ''));
        $parsedDate = $this->parseDate($combined['date'] ?? null);

        return [
            'filename' => trim((string) ($combined['filename'] ?? '')),
            'date' => $parsedDate?->toDateTimeString(),
            'status' => $status,
            'items_extracted' => (int) ($combined['items_extracted'] ?? 0),
            'error_details' => trim((string) ($combined['error_details'] ?? '')),
            'duration_sec' => (float) ($combined['duration_sec'] ?? 0),
            'is_error' => $status === 'Error',
            'is_warning' => $status === 'Warning: No items found',
        ];
    }

    private function parseDate(mixed $date): ?CarbonInterface
    {
        if (!is_string($date) || trim($date) === '') {
            return null;
        }

        try {
            return Carbon::parse($date);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param Collection<int, array<string, mixed>> $rows
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function filterRows(Collection $rows, ?string $filename, bool $onlyToday): Collection
    {
        if ($filename !== null && $filename !== '') {
            $safeFilename = basename($filename);

            return $rows->filter(static fn (array $row): bool => $row['filename'] === $safeFilename);
        }

        if (!$onlyToday) {
            return $rows;
        }

        return $rows->filter(static fn (array $row): bool => is_string($row['date']) && Carbon::parse($row['date'])->isToday());
    }
}
