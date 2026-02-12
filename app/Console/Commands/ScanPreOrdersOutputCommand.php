<?php

namespace App\Console\Commands;

use App\Services\PreOrderImportService;
use Illuminate\Console\Command;

class ScanPreOrdersOutputCommand extends Command
{
    protected $signature = 'preorders:scan-output {--path=} {--pattern=}';

    protected $description = 'Scan output folder and import CSV files as pre-orders';

    public function handle(PreOrderImportService $importService): int
    {
        $configuredPath = config('pre_orders.output_path', 'output');
        $configuredPattern = config('pre_orders.file_pattern', '*.csv');

        $pathOption = $this->option('path') ?: $configuredPath;
        $patternOption = $this->option('pattern') ?: $configuredPattern;

        $path = base_path($pathOption);

        if (!is_dir($path)) {
            $this->warn("Directory not found: {$path}");
            return self::SUCCESS;
        }

        $files = glob($path . '/' . ltrim($patternOption, '/')) ?: [];
        $importedCount = 0;

        foreach ($files as $file) {
            if ($importService->importCsvFile($file)) {
                $importedCount++;
                $this->info('Imported: ' . basename($file));
            }
        }

        $this->info("Done. Imported {$importedCount} file(s) from {$pathOption} (pattern: {$patternOption}).");

        return self::SUCCESS;
    }
}
