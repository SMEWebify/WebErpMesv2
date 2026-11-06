<?php

namespace Tests\Feature\Products;

use App\Models\Integrations\IntegrationEndpoint;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsServices;
use App\Models\Methods\MethodsUnits;
use App\Models\Planning\Task;
use App\Models\Products\Nest;
use App\Models\Products\Products;
use App\Models\Products\StockLocation;
use App\Models\Products\StockLocationProducts;
use App\Models\Products\StockMove;
use App\Models\User;
use App\Models\Workflow\OrderLineDetails;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Services\Stock\NestRecorder;
use App\Services\Stock\SheetRemnants;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Imbrications réalisées : une tôle découpée pour plusieurs lignes de commande,
 * saisie à la main ou reçue de Nest2Prod MES (`stock.consumed` + `nest.updated`).
 */
class NestRecorderTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'nest-inbound-secret';

    private User $user;
    private Products $sheet;
    private StockLocationProducts $slp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        foreach (['stock-lot-serial-management', 'products-menu'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $this->user->givePermissionTo($permission);
        }

        StockLocation::query()->exists() || StockLocation::factory()->create();
        $service = MethodsServices::factory()->create(['type' => 3]);
        $family = MethodsFamilies::factory()->create(['methods_services_id' => $service->id, 'nest_type' => 'sheet']);
        MethodsUnits::factory()->create(['code' => 'PC']);

        $this->sheet = Products::factory()->create([
            'methods_families_id' => $family->id,
            'material' => 'S235', 'thickness' => 3,
            'x_size' => 1500, 'y_size' => 3000, 'z_size' => 3,
        ]);
        $this->slp = StockLocationProducts::factory()->create(['products_id' => $this->sheet->id]);
        $this->slp->update(['unit_cost' => 90]);
        StockMove::create([
            'user_id' => $this->user->id, 'qty' => 4, 'stock_location_products_id' => $this->slp->id, 'typ_move' => 3,
            'x_size' => 1500, 'y_size' => 3000, 'z_size' => 3, 'component_price' => 90,
        ]);
    }

    /** Ligne de commande avec sa tâche matière (composant = la tôle) et la taille de sa pièce. */
    private function lineWithTask(float $x, float $y): Task
    {
        $order = Orders::factory()->create();
        $line = OrderLines::factory()->create(['orders_id' => $order->id]);
        OrderLineDetails::updateOrCreate(['order_lines_id' => $line->id], ['x_size' => $x, 'y_size' => $y]);

        return Task::factory()->create([
            'quote_lines_id' => null, 'products_id' => null, 'sub_assembly_id' => null,
            'order_lines_id' => $line->id, 'component_id' => $this->sheet->id,
        ]);
    }

    private function fullSheetKey(): string
    {
        return app(SheetRemnants::class)->pieces($this->slp->fresh())[0]['key'];
    }

    // ------------------------------------------------------------------
    // Saisie à la main
    // ------------------------------------------------------------------

    public function test_one_sheet_is_split_across_two_order_lines_by_part_area(): void
    {
        $a = $this->lineWithTask(1000, 1000); // 1 m² × 1
        $b = $this->lineWithTask(500, 1000);  // 0,5 m² × 2

        $this->post(route('products.nests.store'), [
            'stock_location_products_id' => $this->slp->id,
            'piece' => $this->fullSheetKey(),
            'sheet_qty' => 1,
            'name' => 'PRG-001',
            'parts' => [$a->id => 1, $b->id => 2],
            'remnants' => [['x_size' => 1500, 'y_size' => 1000]],
        ])->assertRedirect()->assertSessionHas('success');

        $nest = Nest::sole();
        $this->assertSame('manual', $nest->source);
        $this->assertSame(2, $nest->parts()->count());

        $exit = StockMove::where('typ_move', 2)->sole();
        $this->assertSame($nest->id, (int) $exit->nest_id);
        $this->assertEquals(90.0, (float) $exit->component_price);
        $offcut = StockMove::whereNotNull('remnant_source_move_id')->sole();
        $this->assertSame($nest->id, (int) $offcut->nest_id);

        // 90 € − chute 1,5 m² / 4,5 m² (30 €) = 60 €, partagés moitié-moitié (1 m² chacun).
        $allocation = app(NestRecorder::class)->allocation($nest);
        $this->assertSame('area', $allocation['basis']);
        $this->assertEquals(60.0, $allocation['cost']);
        $this->assertEquals([30.0, 30.0], array_column($allocation['lines'], 'cost'));

        $this->get(route('products.nests.show', ['nest' => $nest->id]))->assertOk()->assertSee('PRG-001');
        $this->get(route('products.nests'))->assertOk()->assertSee('PRG-001');
    }

    public function test_a_nest_without_parts_is_refused_and_nothing_is_written(): void
    {
        $this->lineWithTask(1000, 1000);

        $this->post(route('products.nests.store'), [
            'stock_location_products_id' => $this->slp->id,
            'piece' => $this->fullSheetKey(),
            'sheet_qty' => 1,
            'name' => 'PRG-002',
            'parts' => [],
        ])->assertSessionHas('error');

        $this->assertSame(0, Nest::count());
        $this->assertSame(0, StockMove::where('typ_move', 2)->count());
    }

    public function test_a_task_using_another_sheet_is_refused(): void
    {
        $other = Task::factory()->create([
            'quote_lines_id' => null, 'products_id' => null, 'sub_assembly_id' => null,
            'order_lines_id' => OrderLines::factory()->create(['orders_id' => Orders::factory()->create()->id])->id,
            'component_id' => Products::factory()->create()->id,
        ]);

        $this->post(route('products.nests.store'), [
            'stock_location_products_id' => $this->slp->id,
            'piece' => $this->fullSheetKey(),
            'sheet_qty' => 1,
            'name' => 'PRG-003',
            'parts' => [$other->id => 1],
        ])->assertSessionHas('error');

        $this->assertSame(0, Nest::count());
    }

    public function test_the_declaration_screen_lists_the_tasks_using_the_sheet(): void
    {
        $task = $this->lineWithTask(1000, 1000);

        $this->get(route('products.nests.create', ['stock_line' => $this->slp->id]))
            ->assertOk()
            ->assertSee('name="parts[' . $task->id . ']"', false);
    }

    // ------------------------------------------------------------------
    // Nest2Prod MES
    // ------------------------------------------------------------------

    private function n2pEndpoint(): void
    {
        IntegrationEndpoint::query()->where('system_code', 'n2p')->delete();
        IntegrationEndpoint::create([
            'name' => 'N2P inbound test', 'system_code' => 'n2p',
            'direction' => IntegrationEndpoint::DIRECTION_INBOUND,
            'auth_method' => IntegrationEndpoint::AUTH_HMAC, 'hmac_secret' => self::SECRET,
            'hmac_header' => 'X-N2P-Signature-256', 'timestamp_header' => 'X-N2P-Timestamp',
            'timestamp_tolerance_s' => 300, 'is_active' => true, 'events' => [], 'verify_ssl' => true,
        ]);
    }

    private function send(string $eventType, array $data): TestResponse
    {
        $body = json_encode([
            'event_id' => (string) Str::uuid(), 'event_type' => $eventType,
            'occurred_at' => Carbon::now()->toIso8601String(), 'source' => 'n2p', 'data' => $data,
        ]);
        $timestamp = time();

        return $this->call('POST', '/api/integrations/n2p/inbound', [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_N2P_TIMESTAMP' => (string) $timestamp,
            'HTTP_X_N2P_SIGNATURE_256' => hash_hmac('sha256', $timestamp . '.' . $body, self::SECRET),
        ], $body);
    }

    private function receivedLot(): string
    {
        return 'MV-' . StockMove::where('typ_move', 3)->value('id');
    }

    private function nestPayload(Task $a, Task $b, int $status = 6): array
    {
        return [
            'nest_id' => 77, 'nest_name' => 'NEST-2026-042', 'status' => $status,
            'finished_at' => '2026-11-06T10:00:00+00:00', 'machine_name' => 'Laser 1',
            'material' => 'S235', 'thickness' => 3, 'sheet_x' => 1500, 'sheet_y' => 3000,
            'sheet_qty' => 1, 'sheet_qty_done' => 1, 'sheet_usage' => '72,5 %',
            'stock_lots' => [['external_ref' => $this->receivedLot(), 'qty_consumed' => 1]],
            'parts' => [
                ['of_code' => 'OF' . $a->order_lines_id, 'line_ref' => (string) $a->order_lines_id, 'part_name' => 'P-A', 'planned_qty' => 1, 'good_qty' => 1, 'bad_qty' => 0, 'area_mm2' => 1_000_000],
                ['of_code' => 'OF' . $b->order_lines_id, 'line_ref' => (string) $b->order_lines_id, 'part_name' => 'P-B', 'planned_qty' => 2, 'good_qty' => 2, 'bad_qty' => 0, 'area_mm2' => 500_000],
                ['of_code' => 'OF999999', 'part_name' => 'P-X', 'planned_qty' => 1, 'area_mm2' => 100_000],
            ],
            'remnants' => [
                ['width' => 1500, 'height' => 1000, 'quantity' => 1, 'name' => 'CH-1', 'is_available' => true],
                ['width' => 200, 'height' => 200, 'quantity' => 1, 'name' => 'CH-2', 'is_available' => false],
            ],
        ];
    }

    public function test_n2p_consumption_is_linked_to_its_nest(): void
    {
        $this->n2pEndpoint();

        $this->send('stock.consumed', [
            'stock_lot_external_ref' => $this->receivedLot(), 'qty' => 1,
            'consumed_by' => ['nest_id' => 77, 'nest_name' => 'NEST-2026-042'],
        ])->assertOk();

        $nest = Nest::sole();
        $this->assertSame(['n2p', '77', 'NEST-2026-042'], [$nest->source, $nest->external_id, $nest->name]);
        $this->assertSame($this->sheet->id, (int) $nest->product_id);
        $move = StockMove::where('typ_move', 2)->sole();
        $this->assertSame($nest->id, (int) $move->nest_id);
        $this->assertEquals(90.0, (float) $move->component_price);
    }

    public function test_nest_updated_maps_parts_to_order_lines_and_records_offcuts_once(): void
    {
        $this->n2pEndpoint();
        $a = $this->lineWithTask(1000, 1000);
        $b = $this->lineWithTask(500, 1000);

        $this->send('stock.consumed', [
            'stock_lot_external_ref' => $this->receivedLot(), 'qty' => 1,
            'consumed_by' => ['nest_id' => 77, 'nest_name' => 'NEST-2026-042'],
        ])->assertOk();
        $this->send('nest.updated', $this->nestPayload($a, $b))->assertOk();
        // Rejoué (nouvel event_id) : pièces remplacées, chutes pas recréées.
        $this->send('nest.updated', $this->nestPayload($a, $b))->assertOk();

        $nest = Nest::sole();
        $this->assertSame(['done', 'Laser 1'], [$nest->status, $nest->machine]);
        $this->assertEquals(72.5, $nest->usage_percent);
        $this->assertSame(3, $nest->parts()->count());
        $this->assertSame($a->id, (int) $nest->parts()->where('part_ref', 'P-A')->value('task_id'));
        $this->assertNull($nest->parts()->where('part_ref', 'P-X')->value('order_line_id'));

        $offcut = StockMove::whereNotNull('remnant_source_move_id')->sole(); // CH-2 indisponible : ignorée
        $this->assertEquals([1500.0, 1000.0], [(float) $offcut->x_size, (float) $offcut->y_size]);
        $this->assertSame($nest->id, (int) $offcut->nest_id);
    }

    public function test_offcuts_wait_for_the_consumption_when_the_nest_arrives_first(): void
    {
        $this->n2pEndpoint();
        $a = $this->lineWithTask(1000, 1000);
        $b = $this->lineWithTask(500, 1000);

        $this->send('nest.updated', $this->nestPayload($a, $b))->assertOk();
        $this->assertSame(0, StockMove::whereNotNull('remnant_source_move_id')->count());

        $this->send('stock.consumed', [
            'stock_lot_external_ref' => $this->receivedLot(), 'qty' => 1,
            'consumed_by' => ['nest_id' => 77],
        ])->assertOk();

        $this->assertSame(1, Nest::count());
        $this->assertSame(1, StockMove::whereNotNull('remnant_source_move_id')->count());
    }

    public function test_an_impossible_offcut_does_not_make_n2p_retry_forever(): void
    {
        $this->n2pEndpoint();
        $a = $this->lineWithTask(1000, 1000);
        $b = $this->lineWithTask(500, 1000);
        $payload = $this->nestPayload($a, $b);
        $payload['remnants'] = [['width' => 4000, 'height' => 4000, 'quantity' => 1]];

        $this->send('stock.consumed', [
            'stock_lot_external_ref' => $this->receivedLot(), 'qty' => 1, 'consumed_by' => ['nest_id' => 77],
        ])->assertOk();
        $this->send('nest.updated', $payload)->assertOk();

        $this->assertSame(0, StockMove::whereNotNull('remnant_source_move_id')->count());
        $this->assertNotNull(Nest::sole()->remnants_recorded_at);
    }

    public function test_a_nest_still_in_progress_records_no_offcut(): void
    {
        $this->n2pEndpoint();
        $a = $this->lineWithTask(1000, 1000);
        $b = $this->lineWithTask(500, 1000);

        $this->send('stock.consumed', [
            'stock_lot_external_ref' => $this->receivedLot(), 'qty' => 1, 'consumed_by' => ['nest_id' => 77],
        ])->assertOk();
        $this->send('nest.updated', $this->nestPayload($a, $b, 3))->assertOk();

        $this->assertSame('in_progress', Nest::sole()->status);
        $this->assertSame(0, StockMove::whereNotNull('remnant_source_move_id')->count());
    }
}
