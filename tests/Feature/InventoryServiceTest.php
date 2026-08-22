<?php

namespace Tests\Feature;

use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsServices;
use App\Models\Methods\MethodsUnits;
use App\Models\Products\Inventory;
use App\Models\Products\InventoryDetail;
use App\Models\Products\Products;
use App\Models\Products\StockLocation;
use App\Models\Products\StockLocationProducts;
use App\Models\Products\StockMove;
use App\Services\Inventory\InventoryService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InventoryService::class);
    }

    public function test_create_snapshots_current_stock_per_slp_and_batch(): void
    {
        $slp = $this->makeSlp();

        // Two batches on the same SLP: expect two snapshot rows.
        StockMove::create(['stock_location_products_id' => $slp->id, 'typ_move' => 3, 'qty' => 10, 'user_id' => 1, 'batch_id' => null]);
        StockMove::create(['stock_location_products_id' => $slp->id, 'typ_move' => 3, 'qty' => 5, 'user_id' => 1, 'batch_id' => 42]);

        $inventory = $this->service->create(['scope_type' => 'all'], 1);

        $this->assertCount(2, $inventory->details);

        $noBatch = $inventory->details->firstWhere('batch_id', null);
        $withBatch = $inventory->details->firstWhere('batch_id', 42);

        $this->assertNotNull($noBatch);
        $this->assertNotNull($withBatch);
        $this->assertEquals(10.0, (float) $noBatch->theoretical_qty);
        $this->assertEquals(5.0, (float) $withBatch->theoretical_qty);
    }

    public function test_validate_generates_typ_move_1_for_positive_variance(): void
    {
        $slp = $this->makeSlp();
        StockMove::create(['stock_location_products_id' => $slp->id, 'typ_move' => 3, 'qty' => 10, 'user_id' => 1]);

        $inventory = $this->service->create(['scope_type' => 'all'], 1);

        // Counter found 12 units instead of the expected 10.
        $inventory->details->first()->update(['counted_qty' => 12, 'status' => InventoryDetail::STATUS_VALIDATED]);

        $this->service->validate($inventory->fresh(), 1);

        $this->assertDatabaseHas('stock_moves', [
            'stock_location_products_id' => $slp->id,
            'typ_move'                   => 1,
            'qty'                        => 2,
            'inventory_id'               => $inventory->id,
        ]);
    }

    public function test_validate_generates_typ_move_15_for_negative_variance(): void
    {
        $slp = $this->makeSlp();
        StockMove::create(['stock_location_products_id' => $slp->id, 'typ_move' => 3, 'qty' => 10, 'user_id' => 1]);

        $inventory = $this->service->create(['scope_type' => 'all'], 1);
        $inventory->details->first()->update(['counted_qty' => 7, 'status' => InventoryDetail::STATUS_VALIDATED]);

        $this->service->validate($inventory->fresh(), 1);

        $this->assertDatabaseHas('stock_moves', [
            'stock_location_products_id' => $slp->id,
            'typ_move'                   => 15,
            'qty'                        => 3,
            'inventory_id'               => $inventory->id,
        ]);
    }

    public function test_validate_generates_no_move_when_variance_is_zero(): void
    {
        $slp = $this->makeSlp();
        StockMove::create(['stock_location_products_id' => $slp->id, 'typ_move' => 3, 'qty' => 10, 'user_id' => 1]);

        $inventory = $this->service->create(['scope_type' => 'all'], 1);
        $inventory->details->first()->update(['counted_qty' => 10]);

        $initialMoves = StockMove::count();

        $this->service->validate($inventory->fresh(), 1);

        $this->assertEquals($initialMoves, StockMove::count(), 'No regularisation move should be created when counted == theoretical');
    }

    public function test_validate_marks_inventory_validated_and_locks_it(): void
    {
        $slp = $this->makeSlp();
        StockMove::create(['stock_location_products_id' => $slp->id, 'typ_move' => 3, 'qty' => 5, 'user_id' => 1]);

        $inventory = $this->service->create(['scope_type' => 'all'], 1);
        $inventory->details->first()->update(['counted_qty' => 5]);

        $this->service->validate($inventory->fresh(), 1);

        $inventory->refresh();
        $this->assertEquals(Inventory::STATUS_VALIDATED, $inventory->statu);
        $this->assertTrue($inventory->isLocked());

        // Second validate must throw.
        $this->expectException(DomainException::class);
        $this->service->validate($inventory, 1);
    }

    public function test_cancel_locks_inventory_without_generating_moves(): void
    {
        $slp = $this->makeSlp();
        StockMove::create(['stock_location_products_id' => $slp->id, 'typ_move' => 3, 'qty' => 5, 'user_id' => 1]);

        $inventory = $this->service->create(['scope_type' => 'all'], 1);
        $inventory->details->first()->update(['counted_qty' => 999]); // Wildly wrong count; should not matter.

        $initialMoves = StockMove::count();

        $this->service->cancel($inventory->fresh());

        $inventory->refresh();
        $this->assertEquals(Inventory::STATUS_CANCELLED, $inventory->statu);
        $this->assertEquals($initialMoves, StockMove::count(), 'Cancelling must never emit stock moves');
    }

    public function test_detect_stock_changes_flags_products_whose_stock_moved_since_snapshot(): void
    {
        $slp = $this->makeSlp();
        StockMove::create(['stock_location_products_id' => $slp->id, 'typ_move' => 3, 'qty' => 10, 'user_id' => 1]);

        $inventory = $this->service->create(['scope_type' => 'all'], 1);

        // A parallel task pick-up happens after the snapshot.
        StockMove::create(['stock_location_products_id' => $slp->id, 'typ_move' => 2, 'qty' => 3, 'user_id' => 1]);

        $mismatches = $this->service->detectStockChanges($inventory->fresh());

        $this->assertNotEmpty($mismatches, 'Concurrent movement should be detected during validation');
    }

    public function test_validate_refuses_when_stock_changed_since_snapshot(): void
    {
        $slp = $this->makeSlp();
        StockMove::create(['stock_location_products_id' => $slp->id, 'typ_move' => 3, 'qty' => 10, 'user_id' => 1]);

        $inventory = $this->service->create(['scope_type' => 'all'], 1);
        $inventory->details->first()->update(['counted_qty' => 10]);

        // Concurrent consumption between snapshot and validation.
        StockMove::create(['stock_location_products_id' => $slp->id, 'typ_move' => 2, 'qty' => 3, 'user_id' => 1]);

        $this->expectException(DomainException::class);
        $this->service->validate($inventory->fresh(), 1);
    }

    public function test_snapshot_splits_same_product_by_geometry(): void
    {
        // Sheet-metal case from Altior: same product (acier ep 2), one full
        // 1500x3000 sheet and one 1000x1000 offcut in the same bin. They
        // MUST snapshot as two distinct lines, or the counter cannot tell
        // them apart when checking the bin.
        $slp = $this->makeSlp();

        StockMove::create([
            'stock_location_products_id' => $slp->id,
            'typ_move' => 3, 'qty' => 1, 'user_id' => 1,
            'x_size' => 1500, 'y_size' => 3000, 'z_size' => 2,
        ]);
        StockMove::create([
            'stock_location_products_id' => $slp->id,
            'typ_move' => 3, 'qty' => 1, 'user_id' => 1,
            'x_size' => 1000, 'y_size' => 1000, 'z_size' => 2,
        ]);

        $inventory = $this->service->create(['scope_type' => 'all'], 1);

        $this->assertCount(2, $inventory->details, 'Full sheet and offcut must snapshot as two distinct lines');

        $full   = $inventory->details->firstWhere('x_size', 1500);
        $offcut = $inventory->details->firstWhere('x_size', 1000);

        $this->assertNotNull($full);
        $this->assertNotNull($offcut);
        $this->assertEquals(3000, (float) $full->y_size);
        $this->assertEquals(1000, (float) $offcut->y_size);
    }

    public function test_validate_propagates_geometry_to_regularisation_move(): void
    {
        // If we forget to carry x_size / y_size onto the stock_move, the
        // regularised offcut becomes untraceable in nesting / reservations.
        $slp = $this->makeSlp();
        StockMove::create([
            'stock_location_products_id' => $slp->id,
            'typ_move' => 3, 'qty' => 1, 'user_id' => 1,
            'x_size' => 1000, 'y_size' => 1000, 'z_size' => 2,
        ]);

        $inventory = $this->service->create(['scope_type' => 'all'], 1);
        $inventory->details->first()->update(['counted_qty' => 2]);

        $this->service->validate($inventory->fresh(), 1);

        $this->assertDatabaseHas('stock_moves', [
            'stock_location_products_id' => $slp->id,
            'typ_move'   => 1,
            'qty'        => 1,
            'x_size'     => 1000,
            'y_size'     => 1000,
            'z_size'     => 2,
            'inventory_id' => $inventory->id,
        ]);
    }

    public function test_scope_by_location_limits_snapshot_to_matching_slps(): void
    {
        $slpInScope = $this->makeSlp();
        $slpOutOfScope = $this->makeSlp();

        StockMove::create(['stock_location_products_id' => $slpInScope->id, 'typ_move' => 3, 'qty' => 5, 'user_id' => 1]);
        StockMove::create(['stock_location_products_id' => $slpOutOfScope->id, 'typ_move' => 3, 'qty' => 8, 'user_id' => 1]);

        $inventory = $this->service->create([
            'scope_type' => 'location',
            'scope_ids'  => [$slpInScope->stock_locations_id],
        ], 1);

        $this->assertCount(1, $inventory->details);
        $this->assertEquals($slpInScope->id, $inventory->details->first()->stock_location_products_id);
    }

    private function makeSlp(): StockLocationProducts
    {
        StockLocation::query()->exists() || StockLocation::factory()->create();

        $service = MethodsServices::factory()->create(['type' => 3]);
        $family = MethodsFamilies::factory()->create(['methods_services_id' => $service->id]);
        $unit = MethodsUnits::factory()->create(['code' => 'PC']);
        $product = Products::factory()->create([
            'methods_families_id' => $family->id,
            'methods_units_id'    => $unit->id,
        ]);

        return StockLocationProducts::factory()->create([
            'products_id' => $product->id,
        ]);
    }
}
