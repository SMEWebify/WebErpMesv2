<?php

namespace Tests\Unit;

use App\Services\InvoiceReportInterpreter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

class InvoiceReportInterpreterTest extends TestCase
{
    private string $reportDirectory;
    private string $reportPath;
    private ?string $backupContent = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reportDirectory = storage_path('app/python/output');
        $this->reportPath = $this->reportDirectory . '/processing_report.csv';
        Config::set('pre_orders.output_path', $this->reportDirectory);

        if (is_file($this->reportPath)) {
            $this->backupContent = file_get_contents($this->reportPath) ?: null;
        }

        File::ensureDirectoryExists($this->reportDirectory);
    }

    protected function tearDown(): void
    {
        if ($this->backupContent !== null) {
            file_put_contents($this->reportPath, $this->backupContent);
        } elseif (is_file($this->reportPath)) {
            unlink($this->reportPath);
        }

        parent::tearDown();
    }

    public function test_it_filters_today_rows_and_marks_errors_and_warnings(): void
    {
        $today = Carbon::today()->toDateString();
        $yesterday = Carbon::yesterday()->toDateString();

        $csv = implode("\n", [
            'filename,date,status,items_extracted,error_details,duration_sec',
            "invoice_today_ok.pdf,{$today},Success,3,,0.5",
            "invoice_today_warn.pdf,{$today},Warning: No items found,0,No lines,0.8",
            "invoice_yesterday_error.pdf,{$yesterday},Error,0,OCR timeout,1.5",
        ]);

        file_put_contents($this->reportPath, $csv);

        $service = new InvoiceReportInterpreter();
        $rows = $service->getReport();

        $this->assertCount(2, $rows);
        $this->assertTrue($rows[1]['is_warning']);
        $this->assertFalse($rows[0]['is_error']);
    }

    public function test_it_can_filter_by_filename_and_detect_failures(): void
    {
        $today = Carbon::today()->toDateString();

        $csv = implode("\n", [
            'filename,date,status,items_extracted,error_details,duration_sec',
            "invoice_1.pdf,{$today},Error,0,Parse error,1.2",
            "invoice_2.pdf,{$today},Success,5,,0.6",
        ]);

        file_put_contents($this->reportPath, $csv);

        $service = new InvoiceReportInterpreter();

        $invoiceOneRows = $service->getReport('invoice_1.pdf');

        $this->assertCount(1, $invoiceOneRows);
        $this->assertSame('invoice_1.pdf', $invoiceOneRows[0]['filename']);
        $this->assertTrue($service->hasFailures('invoice_1.pdf'));
        $this->assertFalse($service->hasFailures('invoice_2.pdf'));
    }

    public function test_absolute_posix_output_path_is_not_doubled(): void
    {
        // A leading "/" must be treated as an absolute path, not re-prefixed with
        // base_path() (regression: the path got doubled, breaking the suite on Linux).
        Config::set('pre_orders.output_path', '/var/data/wem/output');

        $method = new ReflectionMethod(InvoiceReportInterpreter::class, 'getReportPath');
        $method->setAccessible(true);
        $resolved = $method->invoke(new InvoiceReportInterpreter());

        $this->assertStringStartsWith('/var/data/wem/output', $resolved);
        $this->assertStringNotContainsString(base_path(), $resolved);
    }

    public function test_it_throws_exception_when_report_is_missing(): void
    {
        if (is_file($this->reportPath)) {
            unlink($this->reportPath);
        }

        $service = new InvoiceReportInterpreter();

        $this->expectException(RuntimeException::class);
        $service->getReport();
    }
}
