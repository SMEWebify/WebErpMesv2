<?php

namespace App\Console\Commands;

use App\Services\PreOrderImportService;
use Illuminate\Console\Command;

class ScanPreOrdersOutputCommand extends Command
{
    protected $signature = 'preorders:scan-output {--path=output}';

    protected $description = 'Scan output folder and import CSV files as pre-orders';

    public function handle(PreOrderImportService $importService): int
    {
        $path = base_path($this->option('path'));

        if (!is_dir($path)) {
            $this->warn("Directory not found: {$path}");
            return self::SUCCESS;
        }

        $files = glob($path . '/*.csv') ?: [];
        $importedCount = 0;

        foreach ($files as $file) {
            if ($importService->importCsvFile($file)) {
                $importedCount++;
                $this->info('Imported: ' . basename($file));
            }
        }

        $this->info("Done. Imported {$importedCount} file(s).");

        return self::SUCCESS;
    }
}

