<?php

namespace Tests\Feature\Products;

use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsServices;
use App\Models\Methods\MethodsUnits;
use App\Models\Planning\Task;
use App\Models\Products\Batch;
use App\Models\Products\Products;
use App\Models\Products\StockLocationProducts;
use App\Models\Products\StockMove;
use App\Models\User;
use App\Services\Stock\SheetRemnants;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Chutes de tôle : pièces d'une ligne de stock (entière / chute), sortie d'une
 * pièce choisie avec remise en stock de ses chutes, depuis la ligne de stock et
 * depuis l'écran tâche, et besoin tôles du nesting sans les chutes.
 */
class SheetRemnantTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private MethodsFamilies $family;

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

        $service = MethodsServices::factory()->create(['type' => 3]);
        $this->family = MethodsFamilies::factory()->create(['methods_services_id' => $service->id, 'nest_type' => 'sheet']);
        MethodsUnits::factory()->create();
    }

    /** Ligne de stock d'une tôle 1500 × 3000 × 10 avec $qty tôles reçues (géométrie copiée comme à la réception). */
    private function sheetLine(float $qty = 2, ?Batch $batch = null): StockLocationProducts
    {
        $product = Products::factory()->create([
            'methods_families_id' => $this->family->id,
            'material' => 'Acier', 'thickness' => 10,
            'x_size' => 1500, 'y_size' => 3000, 'z_size' => 10,
        ]);
        $slp = StockLocationProducts::factory()->create(['products_id' => $product->id]);
        StockMove::create([
            'user_id' => $this->user->id, 'qty' => $qty, 'stock_location_products_id' => $slp->id, 'typ_move' => 3,
            'x_size' => 1500, 'y_size' => 3000, 'z_size' => 10,
            'batch_id' => $batch?->id, 'tracability' => $batch?->code,
        ]);

        return $slp;
    }

    private function pieces(StockLocationProducts $slp): array
    {
        return app(SheetRemnants::class)->pieces($slp->fresh());
    }

    /** [format, entière/chute, qté] pour comparer simplement. */
    private function summary(StockLocationProducts $slp): array
    {
        return array_map(fn ($p) => [$p['format'], $p['is_remnant'] ? 'chute' : 'entière', $p['qty']], $this->pieces($slp));
    }

    private function sortPiece(StockLocationProducts $slp, string $piece, float $qty, array $remnants = [])
    {
        return $this->post(route('products.stockline.sorting'), [
            'user_id' => $this->user->id,
            'qty' => $qty,
            'typ_move' => 6,
            'stock_location_products_id' => $slp->id,
            'order_line_id' => 'null',
            'task_id' => 'null',
            'piece' => $piece,
            'remnants' => $remnants,
        ]);
    }

    // ------------------------------------------------------------------
    // Pièces
    // ------------------------------------------------------------------

    public function test_the_users_example_one_sheet_and_its_offcuts_remain(): void
    {
        $slp = $this->sheetLine(2);
        $full = $this->pieces($slp)[0];

        // 1000 × 1000 découpé dans un 1500 × 3000 : chute en L = 1500 × 2000 + 500 × 1000.
        $this->sortPiece($slp, $full['key'], 1, [
            ['x_size' => 1500, 'y_size' => 2000, 'qty' => ''],
            ['x_size' => 500, 'y_size' => 1000, 'qty' => 1],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame([
            ['1500 × 3000 × 10', 'entière', 1.0],
            ['1500 × 2000 × 10', 'chute', 1.0],
            ['500 × 1000 × 10', 'chute', 1.0],
        ], $this->summary($slp));
        $this->assertEquals(3, $slp->fresh()->getCurrentStockMove());

        $exit = StockMove::where('typ_move', 6)->sole();
        $this->assertEquals([1500.0, 3000.0, 10.0], [(float) $exit->x_size, (float) $exit->y_size, (float) $exit->z_size]);
        $this->assertSame(2, StockMove::where('remnant_source_move_id', $exit->id)->where('typ_move', SheetRemnants::ENTRY_TYPE)->count());
    }

    public function test_exits_written_before_without_geometry_are_taken_from_full_sheets(): void
    {
        $slp = $this->sheetLine(3);
        StockMove::create(['user_id' => $this->user->id, 'qty' => 1, 'stock_location_products_id' => $slp->id, 'typ_move' => 6]);
        $this->sortPiece($slp, $this->pieces($slp)[0]['key'], 1, [['x_size' => 1000, 'y_size' => 1000]]);
        // Sortie de l'écran tâche, sans géométrie : une tôle entière, pas la chute.
        StockMove::create(['user_id' => $this->user->id, 'qty' => 1, 'stock_location_products_id' => $slp->id, 'typ_move' => 2]);

        $this->assertSame([['1000 × 1000 × 10', 'chute', 1.0]], $this->summary($slp));
        $this->assertEquals(1, $slp->fresh()->getCurrentStockMove());
    }

    public function test_an_offcut_found_at_inventory_counts_as_an_offcut(): void
    {
        $slp = $this->sheetLine(1);
        StockMove::create([
            'user_id' => $this->user->id, 'qty' => 1, 'stock_location_products_id' => $slp->id, 'typ_move' => 1,
            'x_size' => 800, 'y_size' => 1200, 'z_size' => 10,
        ]);

        $this->assertSame([
            ['1500 × 3000 × 10', 'entière', 1.0],
            ['800 × 1200 × 10', 'chute', 1.0],
        ], $this->summary($slp));
    }

    public function test_an_offcut_can_be_taken_out_and_cut_again(): void
    {
        $slp = $this->sheetLine(1);
        $this->sortPiece($slp, $this->pieces($slp)[0]['key'], 1, [['x_size' => 1500, 'y_size' => 2000]]);
        $offcut = collect($this->pieces($slp))->firstWhere('is_remnant', true);

        $this->sortPiece($slp, $offcut['key'], 1, [['x_size' => 1500, 'y_size' => 900]])->assertSessionHas('success');

        $this->assertSame([['1500 × 900 × 10', 'chute', 1.0]], $this->summary($slp));
    }

    public function test_offcuts_keep_the_batch_of_the_sheet(): void
    {
        $product = Products::factory()->create();
        $batch = Batch::create(['code' => 'LOT-7', 'product_id' => $product->id, 'heat_number' => 'C-7']);
        $slp = $this->sheetLine(1, $batch);

        $this->sortPiece($slp, $this->pieces($slp)[0]['key'], 1, [['x_size' => 1000, 'y_size' => 1000]]);

        $offcut = StockMove::whereNotNull('remnant_source_move_id')->sole();
        $this->assertSame($batch->id, (int) $offcut->batch_id);
        $this->assertSame('LOT-7', $offcut->tracability);
        $this->assertSame('C-7', $this->pieces($slp)[0]['heat_number']);
    }

    public function test_an_offcut_is_valued_at_its_share_of_the_sheet_area(): void
    {
        $slp = $this->sheetLine(2);
        $slp->update(['unit_cost' => 90]);

        $this->sortPiece($slp, $this->pieces($slp)[0]['key'], 1, [['x_size' => 1500, 'y_size' => 2000]]);

        // 3 m² sur 4,5 m² : 2/3 de 90.
        $this->assertEquals(60.0, (float) StockMove::whereNotNull('remnant_source_move_id')->sole()->component_price);
    }

    // ------------------------------------------------------------------
    // Refus
    // ------------------------------------------------------------------

    public function test_an_offcut_larger_than_the_sheet_is_refused_and_nothing_is_written(): void
    {
        $slp = $this->sheetLine(1);

        $this->sortPiece($slp, $this->pieces($slp)[0]['key'], 1, [['x_size' => 1600, 'y_size' => 1600]])
            ->assertSessionHas('error');

        $this->assertSame(1, StockMove::count());
    }

    public function test_offcuts_covering_the_whole_sheet_are_refused(): void
    {
        $slp = $this->sheetLine(1);

        $this->sortPiece($slp, $this->pieces($slp)[0]['key'], 1, [
            ['x_size' => 1500, 'y_size' => 2000],
            ['x_size' => 1500, 'y_size' => 1000],
        ])->assertSessionHas('error');

        $this->assertSame(1, StockMove::count());
    }

    public function test_taking_out_more_than_the_piece_holds_is_refused(): void
    {
        $slp = $this->sheetLine(1);

        $this->sortPiece($slp, $this->pieces($slp)[0]['key'], 2)->assertSessionHas('error');

        $this->assertSame(1, StockMove::count());
    }

    public function test_an_offcut_without_a_chosen_piece_is_refused(): void
    {
        $slp = $this->sheetLine(1);

        $this->sortPiece($slp, '', 1, [['x_size' => 1000, 'y_size' => 1000]])->assertSessionHas('error');

        $this->assertSame(1, StockMove::count());
    }

    public function test_the_exit_without_piece_and_empty_offcut_rows_is_unchanged(): void
    {
        $slp = $this->sheetLine(2);

        $this->sortPiece($slp, '', 1, [['x_size' => '', 'y_size' => '', 'qty' => '']])->assertSessionHas('success');

        $exit = StockMove::where('typ_move', 6)->sole();
        $this->assertNull($exit->x_size);
        $this->assertSame(0, StockMove::whereNotNull('remnant_source_move_id')->count());
    }

    // ------------------------------------------------------------------
    // Écran tâche, page, nesting
    // ------------------------------------------------------------------

    public function test_the_task_screen_puts_the_offcut_back_into_stock(): void
    {
        $slp = $this->sheetLine(2);
        $task = Task::factory()->create([
            'quote_lines_id' => null, 'order_lines_id' => null, 'products_id' => null, 'sub_assembly_id' => null,
            'component_id' => $slp->products_id,
        ]);

        $this->postJson(route('production.task.statu.api.good_qty_stock', ['id' => $task->id]), [
            'qty' => 1, 'component_id' => $slp->products_id,
            'remnants' => [['x_size' => 1500, 'y_size' => 2000]],
        ])->assertOk();

        $exit = StockMove::where('typ_move', 2)->sole();
        $offcut = StockMove::whereNotNull('remnant_source_move_id')->sole();
        $this->assertSame($exit->id, (int) $offcut->remnant_source_move_id);
        $this->assertSame($task->id, (int) $offcut->task_id);
        $this->assertSame([
            ['1500 × 3000 × 10', 'entière', 1.0],
            ['1500 × 2000 × 10', 'chute', 1.0],
        ], $this->summary($slp));

        $component = $this->getJson(route('production.task.statu.api.show', ['id' => $task->id]))->json('component');
        $this->assertEquals([1500, 3000], [$component['x_size'], $component['y_size']]);
    }

    public function test_the_task_screen_refuses_an_invalid_offcut_before_taking_anything_out(): void
    {
        $slp = $this->sheetLine(2);
        $task = Task::factory()->create([
            'quote_lines_id' => null, 'order_lines_id' => null, 'products_id' => null, 'sub_assembly_id' => null,
            'component_id' => $slp->products_id,
        ]);

        $this->postJson(route('production.task.statu.api.good_qty_stock', ['id' => $task->id]), [
            'qty' => 1, 'component_id' => $slp->products_id,
            'remnants' => [['x_size' => 4000, 'y_size' => 1000]],
        ])->assertStatus(422);

        $this->assertSame(0, StockMove::where('typ_move', 2)->count());
    }

    public function test_the_stock_line_page_lists_the_pieces(): void
    {
        $slp = $this->sheetLine(2);
        $this->sortPiece($slp, $this->pieces($slp)[0]['key'], 1, [['x_size' => 1500, 'y_size' => 2000]]);

        $this->get(route('products.stockline.show', ['id' => $slp->id]))
            ->assertOk()
            ->assertSee(__('stock_remnants.pieces_title'))
            ->assertSee('1500 × 2000 × 10')
            ->assertSee(__('stock_remnants.remnant_entry'));
    }

    public function test_nesting_sheet_stock_does_not_count_offcuts_as_sheets(): void
    {
        $slp = $this->sheetLine(2);
        $this->sortPiece($slp, $this->pieces($slp)[0]['key'], 1, [['x_size' => 1500, 'y_size' => 2000]]);

        $row = collect($this->getJson(route('nesting.sheet-stock'))->assertOk()->json())
            ->firstWhere('id', $slp->products_id);

        $this->assertEquals(1, $row['stock_qty']);
        $this->assertEquals(1, $row['remnant_qty']);
    }
}
