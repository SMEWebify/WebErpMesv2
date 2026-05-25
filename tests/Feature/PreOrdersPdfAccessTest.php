<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workflow\PreOrder;
use App\Models\Workflow\PreOrderImport;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PreOrdersPdfAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);

        Config::set('filesystems.default', 'local');
        Config::set('pre_orders.input_path', 'pre-orders/input');
        Config::set('pre_orders.output_path', 'output');
        Config::set('pre_orders.done_path', 'output/done');

        Storage::fake('local');
    }

    public function test_it_streams_pdf_from_done_path_for_pre_order_pdf_route(): void
    {
        $this->actingAs(User::factory()->create());

        Storage::disk('local')->put('output/done/dc_metal_2600034108_20260218_160251_600462.pdf', '%PDF-1.4 fake');

        $import = PreOrderImport::create([
            'file_name' => 'batch.csv',
            'checksum' => sha1('batch.csv'),
            'imported_at' => Carbon::now(),
        ]);

        $preOrder = PreOrder::create([
            'pre_order_import_id' => $import->id,
            'source_pdf' => 'dc_metal_2600034108_20260218_160251_600462.pdf',
            'status' => PreOrder::STATUS_PENDING,
        ]);

        $this->get(route('pre-orders.pdf', $preOrder))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_it_uses_basename_when_source_pdf_contains_nested_path(): void
    {
        $this->actingAs(User::factory()->create());

        Storage::disk('local')->put('output/done/dc_metal_2600034098_20260218_160251_599572.pdf', '%PDF-1.4 fake');

        $import = PreOrderImport::create([
            'file_name' => 'batch.csv',
            'checksum' => sha1('batch.csv-2'),
            'imported_at' => Carbon::now(),
        ]);

        $preOrder = PreOrder::create([
            'pre_order_import_id' => $import->id,
            'source_pdf' => 'nested/folder/dc_metal_2600034098_20260218_160251_599572.pdf',
            'status' => PreOrder::STATUS_PENDING,
        ]);

        $this->get(route('pre-orders.pdf', $preOrder))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
