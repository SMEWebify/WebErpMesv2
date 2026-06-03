<?php

namespace App\Console\Commands;

use App\Services\PreOrderImportService;
use Illuminate\Console\Command;

class ScanPreOrdersOutputCommand extends Command
{
    protected $signature = 'preorders:scan-output {--path=} {--pattern=} {--done-path=}';

    protected $description = 'Scan output folder and import CSV files as pre-orders';

    public function handle(PreOrderImportService $importService): int
    {
        $configuredPath = config('pre_orders.output_path', 'output');
        $configuredPattern = config('pre_orders.file_pattern', '*.csv');
        $configuredDonePath = config('pre_orders.done_path', 'output/done');

        $pathOption = $this->option('path') ?: $configuredPath;
        $patternOption = $this->option('pattern') ?: $configuredPattern;
        $donePathOption = $this->option('done-path') ?: $configuredDonePath;

        $path = $this->resolveConfiguredPath($pathOption);

        if (!is_dir($path)) {
            $this->warn("Directory not found: {$path}");
            return self::SUCCESS;
        }

        $files = glob($path . '/' . ltrim($patternOption, '/')) ?: [];
        $importedCount = 0;
        $skippedCount = 0;

        foreach ($files as $file) {
            if ($importService->importCsvFile($file)) {
                $this->moveToDoneFolder($file, $donePathOption);
                $importedCount++;
                $this->info('Imported: ' . basename($file));
            } else {
                $skippedCount++;
                $this->warn('Skipped (duplicate or invalid): ' . basename($file));
            }
        }

        $this->info("Done. Imported {$importedCount} file(s), skipped {$skippedCount} from {$pathOption} (pattern: {$patternOption}).");

        if (count($files) > 0 && $importedCount === 0) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function moveToDoneFolder(string $filePath, string $donePathOption): void
    {
        $doneDirectory = $this->resolveConfiguredPath($donePathOption);

        if (!is_dir($doneDirectory) && !mkdir($doneDirectory, 0775, true) && !is_dir($doneDirectory)) {
            $this->warn("Unable to create done directory: {$doneDirectory}");
            return;
        }

        $destination = $doneDirectory . DIRECTORY_SEPARATOR . basename($filePath);

        if (is_file($destination)) {
            $pathInfo = pathinfo($destination);
            $filename = $pathInfo['filename'] ?? basename($filePath);
            $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
            $destination = $doneDirectory . DIRECTORY_SEPARATOR . $filename . '-' . now()->format('YmdHis') . $extension;
        }

        if (!rename($filePath, $destination)) {
            $this->warn('Unable to move imported file to done folder: ' . basename($filePath));
        }
    }

    private function resolveConfiguredPath(string $pathOption): string
    {
        $normalized = trim(str_replace('\\', '/', $pathOption));

        if ($normalized === '') {
            return base_path();
        }

        if (preg_match('/^[A-Za-z]:\//', $normalized) === 1 || str_starts_with($normalized, '/')) {
            return $normalized;
        }

        return base_path(ltrim($normalized, '/'));
    }
}
