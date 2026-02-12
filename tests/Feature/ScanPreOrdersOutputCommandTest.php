<?php

namespace Tests\Feature;

use App\Services\PreOrderImportService;
use Tests\TestCase;

class ScanPreOrdersOutputCommandTest extends TestCase
{
    public function test_it_moves_imported_csv_file_to_done_folder(): void
    {
        $baseTestPath = base_path('tests_tmp/preorders');
        $outputPath = $baseTestPath . '/output';
        $donePath = $baseTestPath . '/done';

        @mkdir($outputPath, 0775, true);
        @mkdir($donePath, 0775, true);

        $csvPath = $outputPath . '/preorder.csv';
        file_put_contents($csvPath, "reference,product,quantity,unit_price,total_price,source_pdf\nA1,Prod,1,10,10,source.pdf\n");

        $this->mock(PreOrderImportService::class, function ($mock) use ($csvPath) {
            $mock->shouldReceive('importCsvFile')
                ->once()
                ->with($csvPath)
                ->andReturn(true);
        });

        $this->artisan('preorders:scan-output', [
            '--path' => 'tests_tmp/preorders/output',
            '--pattern' => '*.csv',
            '--done-path' => 'tests_tmp/preorders/done',
        ])->assertSuccessful();

        $this->assertFileDoesNotExist($csvPath);
        $this->assertFileExists($donePath . '/preorder.csv');

        @unlink($donePath . '/preorder.csv');
        @rmdir($donePath);
        @rmdir($outputPath);
        @rmdir($baseTestPath);
    }
}
