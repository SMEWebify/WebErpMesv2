<?php

namespace Tests\Feature\Production;

use App\Models\Admin\Factory;
use App\Models\Planning\Task;
use App\Models\User;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Services\Documents\ProductionSheetBuilder;
use App\Services\Production\ScanCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Codes de scan N2P:{of}*{ligne}*{op}*{seq} sur la fiche suiveuse
 * (docs/pointage-scan.md). Option désactivée : ProductionSheetLegacyRenderingTest.
 */
class ProductionSheetScanCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_code_carries_the_business_key_of_the_task(): void
    {
        $task = (new Task())->forceFill(['id' => 7, 'order_lines_id' => 1234, 'code' => 'PLI', 'ordre' => 20]);

        $this->assertSame('N2P:OF1234*1234*PLI*20', ScanCode::forTask($task));
    }

    public function test_a_rank_below_one_leaves_the_seq_empty(): void
    {
        $task = (new Task())->forceFill(['id' => 7, 'order_lines_id' => 1234, 'code' => 'PLI', 'ordre' => 0]);

        $this->assertSame('N2P:OF1234*1234*PLI*', ScanCode::forTask($task));
    }

    public function test_no_code_outside_an_order_line_or_with_a_separator(): void
    {
        $this->assertNull(ScanCode::forTask((new Task())->forceFill(['id' => 7, 'code' => 'PLI', 'ordre' => 10])));
        $this->assertNull(ScanCode::forTask((new Task())->forceFill(['id' => 7, 'order_lines_id' => 1, 'code' => 'PL*I', 'ordre' => 10])));
        $this->assertNull(ScanCode::forTask((new Task())->forceFill(['id' => 7, 'order_lines_id' => 1, 'code' => 'PL|I', 'ordre' => 10])));
    }

    public function test_the_builder_prints_scan_codes_only_when_the_option_is_on(): void
    {
        $order = Orders::factory()->create();
        $line = OrderLines::factory()->create(['orders_id' => $order->id]);
        // Type 1 (fabrication) : la fiche n'imprime que les opérations (TechnicalCut).
        Task::factory()->create(['order_lines_id' => $line->id, 'code' => 'PLI', 'ordre' => 20, 'type' => 1]);

        app()->instance('Factory', new Factory(['task_barre_code' => 'C128', 'production_sheet_scan_codes' => false]));
        $operation = app(ProductionSheetBuilder::class)->build($order)['sheets'][0]['operations'][0];
        $this->assertNotNull($operation['barcode_src']);
        $this->assertArrayNotHasKey('scan_code', $operation);

        app()->instance('Factory', new Factory(['task_barre_code' => 'C128', 'production_sheet_scan_codes' => true]));
        $operation = app(ProductionSheetBuilder::class)->build($order)['sheets'][0]['operations'][0];
        $this->assertNull($operation['barcode_src'], 'le code « n° de tâche » est remplacé');
        $this->assertSame("N2P:OF{$line->id}*{$line->id}*PLI*20", $operation['scan_code']);
        $this->assertStringStartsWith('data:image/png;base64,', $operation['scan_qr_src']);
        $this->assertStringStartsWith('data:image/png;base64,', $operation['scan_barcode_src']);
    }

    public function test_the_sheet_shows_the_qr_the_code128_and_the_readable_code(): void
    {
        $html = view('print/production-sheet', [
            'orderCode' => 'CMD-1',
            'customerName' => 'ACME',
            'customerReference' => null,
            'generatedAt' => Carbon::parse('2026-11-13 08:00'),
            'orderQrSrc' => null,
            'sheets' => collect([array_merge($this->sheet(), ['operations' => [
                $this->operation(10, 'Pliage', [
                    'scan_code' => 'N2P:OF55*55*PLI*10',
                    'scan_qr_src' => 'data:image/png;base64,SCANQR',
                    'scan_barcode_src' => 'data:image/png;base64,SCAN128',
                    'scan_unavailable' => false,
                ]),
                $this->operation(20, 'Soudure', [
                    'scan_code' => null,
                    'scan_qr_src' => null,
                    'scan_barcode_src' => null,
                    'scan_unavailable' => true,
                ]),
            ]])]),
            'totalRequired' => 5.0,
            'totalDelivered' => 0.0,
        ])->render();

        $this->assertStringContainsString('data:image/png;base64,SCANQR', $html);
        $this->assertStringContainsString('data:image/png;base64,SCAN128', $html);
        $this->assertStringContainsString('N2P:OF55*55*PLI*10', $html);
        $this->assertStringContainsString(e(__('production_sheet.ops.scan_unavailable')), $html);
        $this->assertSame(1, substr_count($html, '>' . __('production_sheet.ops.declare') . '<'));
    }

    public function test_a_scanned_code_is_parsed_with_either_separator(): void
    {
        $expected = ['of_code' => 'OF55', 'line_ref' => '55', 'operation_code' => 'PLI', 'seq' => 20];

        $this->assertSame($expected, ScanCode::parse('N2P:OF55*55*PLI*20'));
        $this->assertSame($expected, ScanCode::parse(" N2P:OF55|55|PLI|20\r\n"));
        $this->assertSame($expected, ScanCode::parse('n2p:of55*55*PLI*20'), 'majuscules bloquées');
        $this->assertSame(['of_code' => 'OF1234', 'line_ref' => '', 'operation_code' => 'PLI', 'seq' => 20], ScanCode::parse('N2P:OF1234**PLI*20'));
        $this->assertNull(ScanCode::parse('N2P:OF55*55*PLI*2X'));
        $this->assertNull(ScanCode::parse('N2P:OF55*55*PLI'));
        $this->assertNull(ScanCode::parse('OF55*55*PLI*20'));
    }

    public function test_the_task_screen_resolves_a_scanned_code_or_a_task_number(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);
        $this->actingAs(User::factory()->create());

        $line = OrderLines::factory()->create(['orders_id' => Orders::factory()->create()->id]);
        Task::factory()->create(['order_lines_id' => $line->id, 'code' => 'PLI', 'ordre' => 10]);
        $second = Task::factory()->create(['order_lines_id' => $line->id, 'code' => 'PLI', 'ordre' => 20]);

        $resolve = fn (string $code) => $this->getJson(route('production.task.statu.api.resolve', ['code' => $code]));

        $resolve(ScanCode::forTask($second))->assertOk()->assertJson(['id' => $second->id]);
        $resolve("N2P:OF{$line->id}|{$line->id}|PLI|20")->assertOk()->assertJson(['id' => $second->id]);
        $resolve((string) $second->id)->assertOk()->assertJson(['id' => $second->id]);
        $resolve("N2P:OF{$line->id}*{$line->id}*LASER*20")->assertNotFound()->assertJsonStructure(['message']);
        // OF et ligne qui ne désignent pas la même ligne de commande : refusé.
        $resolve("N2P:OF{$line->id}*{$line->id}9*PLI*20")->assertNotFound();
        $resolve("N2P:OF{$line->id}**PLI*20")->assertOk()->assertJson(['id' => $second->id]);
        $resolve('n\'importe quoi')->assertNotFound();
    }

    private function operation(int $ordre, string $label, array $scan): array
    {
        return [
            'ordre' => $ordre,
            'label' => $label,
            'service' => null,
            'workcenter' => null,
            'planned_time_min' => 30,
            'barcode_src' => null,
            'batches' => [],
        ] + $scan;
    }

    private function sheet(): array
    {
        return [
            'ordre' => 1, 'code' => 'LINE-1', 'label' => 'Platine', 'product_code' => null, 'revision' => null,
            'equipment_tags' => [], 'qty' => 5, 'delivered_qty' => 0, 'unit' => 'U',
            'due_date' => null, 'internal_delay' => null, 'material' => null, 'thickness' => null,
            'x_size' => null, 'y_size' => null, 'z_size' => null, 'weight' => null, 'bend_count' => null,
            'finishing' => null, 'cad_file' => null, 'notes' => null, 'image_src' => null,
            'planned_start' => null, 'planned_end' => null, 'operations' => [], 'components' => [],
        ];
    }
}
