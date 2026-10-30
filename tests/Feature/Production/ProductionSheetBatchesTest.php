<?php

namespace Tests\Feature\Production;

use App\Models\Accounting\AccountingVat;
use App\Models\Admin\Factory;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsServices;
use App\Models\Methods\MethodsUnits;
use App\Models\Planning\Task;
use App\Models\Products\Batch;
use App\Models\Products\Products;
use App\Models\Products\StockLocationProducts;
use App\Models\Products\StockMove;
use App\Models\User;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Services\Documents\ProductionSheetBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\ProductionSheetLegacyRenderingTest;
use Tests\TestCase;

/**
 * Lots consommés (coulées) sur la fiche suiveuse : relus en une requête pour
 * toute la commande, imprimés sous l'opération et dans la colonne « Lot
 * matière » de la nomenclature ; rien n'est ajouté sans lot.
 */
class ProductionSheetBatchesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private AccountingVat $vat;
    private MethodsUnits $unit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        app()->forgetInstance('Factory');
        app()->instance('Factory', Factory::create([
            'name' => 'Chaudronnerie', 'address' => '1 rue', 'zipcode' => '35000', 'city' => 'Rennes',
            'phone_number' => '0', 'mail' => 'a@b.test', 'curency' => 'EUR', 'pdf_header_font_color' => '#dddddd',
        ]));

        $this->vat = AccountingVat::factory()->create(['rate' => 20, 'default' => 1]);
        $this->unit = MethodsUnits::factory()->create(['code' => 'U', 'label' => 'Unité', 'default' => 1]);
        $service = MethodsServices::factory()->create(['type' => 3]);
        MethodsFamilies::factory()->create(['methods_services_id' => $service->id]);
        app()->setLocale('fr');
    }

    public function test_consumed_batches_are_printed_with_their_heat_number(): void
    {
        $order = Orders::factory()->create(['statu' => 1, 'type' => 1]);
        $line = $this->orderLine($order, 1);
        $sheetMetal = Products::factory()->create(['code' => 'TOLE-S235', 'label' => 'Tôle S235']);
        $screw = Products::factory()->create(['code' => 'VIS-M6', 'label' => 'Vis M6']);

        $cutting = $this->task($line, 1, 10, ['label' => 'Découpe laser']);
        $bending = $this->task($line, 1, 20, ['label' => 'Pliage']);
        $plate = $this->task($line, 3, 1, ['label' => 'Tôle', 'component_id' => $sheetMetal->id, 'qty' => 2]);
        $this->task($line, 3, 2, ['label' => 'Vis', 'component_id' => $screw->id, 'qty' => 4]);

        $first = Batch::create(['code' => 'LOT-1', 'product_id' => $sheetMetal->id, 'heat_number' => 'C-48213',
            'supplier_batch_code' => 'L-77', 'certificate_type' => '3.1']);
        $second = Batch::create(['code' => 'LOT-2', 'product_id' => $sheetMetal->id, 'heat_number' => 'C-50001']);
        $slp = StockLocationProducts::factory()->create(['products_id' => $sheetMetal->id]);

        // Composant : deux sorties du premier lot (cumulées) puis une du second.
        $this->exit($slp, $plate, $first, 1.5);
        $this->exit($slp, $plate, $first, 0.5);
        $this->exit($slp, $plate, $second, 1);
        // Opération qui a sorti un lot elle-même (sortie manuelle rattachée à la tâche).
        $this->exit($slp, $cutting, $second, 0.25);
        // Ni une sortie sans lot ni une entrée ne sont des lots consommés.
        StockMove::create(['user_id' => $this->user->id, 'qty' => 9, 'stock_location_products_id' => $slp->id, 'typ_move' => 2, 'task_id' => $plate->id]);
        StockMove::create(['user_id' => $this->user->id, 'qty' => 9, 'stock_location_products_id' => $slp->id, 'typ_move' => 3, 'task_id' => $bending->id, 'batch_id' => $first->id]);

        $data = app(ProductionSheetBuilder::class)->build($order->fresh());
        $sheet = $data['sheets'][0];

        $this->assertSame([
            ['code' => 'LOT-1', 'heat_number' => 'C-48213', 'supplier_batch_code' => 'L-77', 'certificate_type' => '3.1', 'qty' => 2.0],
            ['code' => 'LOT-2', 'heat_number' => 'C-50001', 'supplier_batch_code' => null, 'certificate_type' => null, 'qty' => 1.0],
        ], $sheet['components'][0]['batches']);
        $this->assertSame([], $sheet['components'][1]['batches'], 'Composant pas encore consommé.');
        $this->assertSame('C-50001', $sheet['operations'][0]['batches'][0]['heat_number']);
        $this->assertSame([], $sheet['operations'][1]['batches']);

        $html = view('print/production-sheet', $data)->render();

        // Nomenclature : coulées dans la colonne « Lot matière », vide pour la vis.
        $this->assertStringContainsString('<strong>C-48213</strong> <span class="muted">(LOT-1 · 3.1) × 2</span>', $html);
        $this->assertStringContainsString('<strong>C-50001</strong> <span class="muted">(LOT-2) × 1</span>', $html);
        $this->assertMatchesRegularExpression('#VIS-M6 Vis M6</td>\s*<td>Vis</td>\s*<td class="right">4</td>\s*<td></td>\s*<td></td>#', $html);

        // Opération : une ligne de lots sous la découpe, aucune sous le pliage.
        $this->assertSame(1, substr_count($html, 'class="op-batches"'));
        $this->assertStringContainsString(__('production_sheet.batches.consumed'), $html);
        $this->assertMatchesRegularExpression('#Lot <strong>LOT-2</strong>\s*· coulée <strong>C-50001</strong>\s*· lot fournisseur —\s*· certificat EN 10204 —\s*· qté 0,25#u', $html);
        $this->assertLessThan(strpos($html, 'Pliage'), strpos($html, 'op-batches'));
        $this->assertGreaterThan(strpos($html, 'Découpe laser'), strpos($html, 'op-batches'));
    }

    public function test_batches_are_read_in_one_query_for_the_whole_sheet(): void
    {
        $order = Orders::factory()->create(['statu' => 1, 'type' => 1]);
        $product = Products::factory()->create();
        $slp = StockLocationProducts::factory()->create(['products_id' => $product->id]);
        $countFor = function (int $lines) use ($order, $product, $slp): int {
            foreach (range(1, $lines) as $i) {
                $line = $this->orderLine($order, OrderLines::where('orders_id', $order->id)->count() + 1);
                $component = $this->task($line, 3, 1, ['component_id' => $product->id]);
                $this->task($line, 1, 10);
                $batch = Batch::create(['code' => 'LOT-' . uniqid(), 'product_id' => $product->id, 'heat_number' => 'C-' . $i]);
                $this->exit($slp, $component, $batch, 1);
            }

            DB::flushQueryLog();
            DB::enableQueryLog();
            app(ProductionSheetBuilder::class)->build($order->fresh());
            $queries = collect(DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'batches'))->count();
            DB::disableQueryLog();

            return $queries;
        };

        $this->assertSame(1, $countFor(1));
        $this->assertSame(1, $countFor(3), 'Une seule requête de lots, quel que soit le nombre de lignes.');
    }

    public function test_a_sheet_without_batches_carries_empty_lists_and_renders_as_before(): void
    {
        $order = Orders::factory()->create(['statu' => 1, 'type' => 1]);
        $line = $this->orderLine($order, 1);
        $this->task($line, 1, 10);
        $this->task($line, 3, 1, ['component_id' => Products::factory()->create()->id]);

        $sheet = app(ProductionSheetBuilder::class)->build($order->fresh())['sheets'][0];
        $this->assertSame([[]], array_column($sheet['operations'], 'batches'));
        $this->assertSame([[]], array_column($sheet['components'], 'batches'));

        // Mêmes données que la référence figée : aucun ajout dans le HTML.
        $html = view('print/production-sheet', ProductionSheetLegacyRenderingTest::data())->render();
        $this->assertStringNotContainsString('op-batches', $html);
        $this->assertStringNotContainsString(__('production_sheet.batches.consumed'), $html);
    }

    private function orderLine(Orders $order, int $ordre): OrderLines
    {
        return OrderLines::create([
            'orders_id' => $order->id, 'ordre' => $ordre, 'code' => 'PLAT-' . $ordre, 'label' => 'Platine ' . $ordre,
            'qty' => 2, 'delivered_qty' => 0, 'delivered_remaining_qty' => 2, 'invoiced_qty' => 0, 'invoiced_remaining_qty' => 2,
            'selling_price' => 100, 'discount' => 0, 'internal_delay' => '2026-11-20',
            'methods_units_id' => $this->unit->id, 'accounting_vats_id' => $this->vat->id,
        ]);
    }

    private function task(OrderLines $line, int $type, int $ordre, array $extra = []): Task
    {
        return Task::factory()->create(array_merge([
            'quote_lines_id' => null, 'order_lines_id' => $line->id, 'products_id' => null, 'sub_assembly_id' => null,
            'type' => $type, 'ordre' => $ordre, 'component_id' => null,
        ], $extra));
    }

    private function exit(StockLocationProducts $slp, Task $task, Batch $batch, float $qty): void
    {
        StockMove::create([
            'user_id' => $this->user->id, 'qty' => $qty, 'stock_location_products_id' => $slp->id,
            'typ_move' => 2, 'task_id' => $task->id, 'batch_id' => $batch->id, 'tracability' => $batch->code,
        ]);
    }
}
