<?php

namespace Tests\Feature\Products;

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
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * API des FAO tierces : une imbrication réalisée (tôle, pièces par ligne de
 * commande, chutes) sort la tôle et remet les chutes une seule fois, rejouable.
 */
class InboundNestApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Products $sheet;
    private StockLocationProducts $slp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
        ]);

        $this->user = User::factory()->create();
        Permission::firstOrCreate(['name' => 'stock-lot-serial-management', 'guard_name' => 'web']);
        $this->user->givePermissionTo('stock-lot-serial-management');

        StockLocation::query()->exists() || StockLocation::factory()->create();
        $service = MethodsServices::factory()->create(['type' => 3]);
        $family = MethodsFamilies::factory()->create(['methods_services_id' => $service->id, 'nest_type' => 'sheet']);
        MethodsUnits::factory()->create(['code' => 'PC']);

        $this->sheet = Products::factory()->create([
            'code' => 'TOLE-S235-3', 'methods_families_id' => $family->id,
            'material' => 'S235', 'thickness' => 3, 'x_size' => 1500, 'y_size' => 3000, 'z_size' => 3,
        ]);
        $this->slp = StockLocationProducts::factory()->create(['products_id' => $this->sheet->id]);
        $this->slp->update(['unit_cost' => 90]);
        StockMove::create([
            'user_id' => $this->user->id, 'qty' => 4, 'stock_location_products_id' => $this->slp->id, 'typ_move' => 3,
            'x_size' => 1500, 'y_size' => 3000, 'z_size' => 3, 'component_price' => 90,
        ]);
    }

    private function lineWithTask(): Task
    {
        $line = OrderLines::factory()->create(['orders_id' => Orders::factory()->create()->id]);

        return Task::factory()->create([
            'quote_lines_id' => null, 'products_id' => null, 'sub_assembly_id' => null,
            'order_lines_id' => $line->id, 'component_id' => $this->sheet->id,
        ]);
    }

    private function callApi(array $payload, array $abilities = ['nests:write'])
    {
        Sanctum::actingAs($this->user, $abilities);

        return $this->postJson(route('api.nests.store'), $payload);
    }

    private function payload(array $override = []): array
    {
        $a = $this->lineWithTask();
        $b = $this->lineWithTask();

        return array_replace([
            'system'      => 'lantek',
            'external_id' => 'JOB-881',
            'name'        => 'PRG-881',
            'status'      => 'done',
            'machine'     => 'Laser 1',
            'usage_percent' => 74.5,
            'sheet_qty'   => 1,
            'sheet'       => ['product_code' => 'TOLE-S235-3', 'x_size' => 3000, 'y_size' => 1500],
            'parts'       => [
                ['order_line_id' => $a->order_lines_id, 'part_ref' => 'SUPPORT-A', 'qty' => 4, 'area_mm2' => 250000],
                ['of_code' => 'OF' . $b->order_lines_id, 'part_ref' => 'PATTE-B', 'qty' => 10, 'area_mm2' => 50000],
                ['of_code' => 'OF999999', 'part_ref' => 'INCONNUE', 'qty' => 1],
            ],
            'remnants'    => [['width' => 1500, 'height' => 1000, 'quantity' => 1]],
        ], $override);
    }

    public function test_a_finished_nest_takes_the_sheet_out_and_puts_the_offcut_back(): void
    {
        $response = $this->callApi($this->payload())->assertCreated();

        $nest = Nest::findOrFail($response->json('id'));
        $this->assertSame(Nest::SOURCE_IMPORT, $nest->source);
        $this->assertSame('lantek:JOB-881', $nest->external_id);
        $this->assertSame('Laser 1', $nest->machine);
        $this->assertTrue($response->json('stock.recorded_now'));
        $this->assertSame(1, $response->json('stock.remnants'));

        $exit = StockMove::where('nest_id', $nest->id)->where('typ_move', 2)->sole();
        $this->assertSame(1.0, (float) $exit->qty);
        $this->assertSame(90.0, (float) $exit->component_price);
        $this->assertNull($exit->task_id); // deux tâches servies : pas de tâche sur la sortie

        $offcut = StockMove::whereNotNull('remnant_source_move_id')->sole();
        $this->assertSame($exit->id, $offcut->remnant_source_move_id);
        $this->assertEqualsCanonicalizing([1500.0, 1000.0], [(float) $offcut->x_size, (float) $offcut->y_size]);

        $parts = collect($response->json('parts'));
        $this->assertSame(2, $parts->whereNotNull('task_id')->count());
        $this->assertContains('part_unmatched:INCONNUE', $response->json('warnings'));
        $this->assertSame(3, $nest->parts()->count());
    }

    public function test_a_replay_updates_the_parts_without_taking_the_sheet_out_twice(): void
    {
        $payload = $this->payload();
        $this->callApi($payload)->assertCreated();

        $payload['parts'] = [$payload['parts'][0]];
        $second = $this->callApi($payload)->assertOk();

        $this->assertFalse($second->json('created'));
        $this->assertFalse($second->json('stock.recorded_now'));
        $this->assertTrue($second->json('stock.recorded'));
        $this->assertContains('stock_already_recorded', $second->json('warnings'));
        $this->assertSame(1, Nest::count());
        $this->assertSame(1, StockMove::where('typ_move', 2)->count());
        $this->assertSame(1, StockMove::whereNotNull('remnant_source_move_id')->count());
        $this->assertSame(1, Nest::sole()->parts()->count());
    }

    public function test_a_nest_in_progress_records_parts_but_no_stock_until_it_is_done(): void
    {
        $payload = $this->payload(['status' => 'in_progress']);
        $this->callApi($payload)->assertCreated();
        $this->assertSame(0, StockMove::where('typ_move', 2)->count());

        $payload['status'] = 'done';
        $this->callApi($payload)->assertOk()->assertJsonPath('stock.recorded_now', true);
        $this->assertSame(1, StockMove::where('typ_move', 2)->count());
    }

    public function test_not_enough_sheets_refuses_everything(): void
    {
        $this->callApi($this->payload(['sheet_qty' => 5]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sheet']);

        $this->assertSame(0, Nest::count());
        $this->assertSame(0, StockMove::where('typ_move', 2)->count());
    }

    public function test_an_unknown_sheet_item_or_format_is_refused(): void
    {
        $this->callApi($this->payload(['sheet' => ['product_code' => 'INCONNU']]))->assertStatus(422);
        $this->callApi($this->payload(['sheet' => ['product_code' => 'TOLE-S235-3', 'x_size' => 2000, 'y_size' => 1000]]))->assertStatus(422);

        $this->assertSame(0, Nest::count());
    }

    public function test_an_offcut_larger_than_the_sheet_refuses_everything(): void
    {
        $this->callApi($this->payload(['remnants' => [['width' => 4000, 'height' => 4000]]]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sheet']);

        $this->assertSame(0, Nest::count());
        $this->assertSame(0, StockMove::where('typ_move', 2)->count());
    }

    public function test_stock_can_be_left_alone(): void
    {
        $this->callApi($this->payload(['consume_stock' => false]))->assertCreated()->assertJsonPath('stock.recorded', false);

        $this->assertSame(0, StockMove::where('typ_move', 2)->count());
        $this->assertSame(0, StockMove::whereNotNull('remnant_source_move_id')->count());
    }

    public function test_token_without_the_ability_or_user_without_the_permission_is_refused(): void
    {
        $this->callApi($this->payload(), ['prospects:write'])->assertForbidden();

        $this->user->permissions()->detach();
        $this->user->forgetCachedPermissions();
        $this->callApi($this->payload())->assertForbidden();

        $this->assertSame(0, Nest::count());
    }
}
