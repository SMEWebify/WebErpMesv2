<?php

namespace Tests\Feature;

use App\Jobs\PushSheetLotToN2P;
use App\Models\Integrations\IntegrationEndpoint;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsServices;
use App\Models\Methods\MethodsUnits;
use App\Models\Products\Products;
use App\Models\Products\StockLocation;
use App\Models\Products\StockLocationProducts;
use App\Models\Products\StockMove;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SheetLotEmitterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        IntegrationEndpoint::query()->where('system_code', 'n2p')->delete();
        IntegrationEndpoint::create([
            'name'         => 'N2P outbound test',
            'system_code'  => 'n2p',
            'direction'    => IntegrationEndpoint::DIRECTION_OUTBOUND,
            'url'          => 'https://n2p.test',
            'auth_method'  => IntegrationEndpoint::AUTH_NONE,
            'verify_ssl'   => false,
            'is_active'    => true,
        ]);
    }

    public function test_sheet_metal_reception_dispatches_push_job(): void
    {
        Queue::fake();

        $slp = $this->makeSlp(
            serviceType: 3,
            unitCode: 'PC',
            productOverrides: [
                'material'  => 'S235JR',
                'thickness' => 3,
                'x_size'    => 1250,
                'y_size'    => 2500,
                'weight'    => 58.9,
            ],
        );

        StockMove::create([
            'stock_location_products_id' => $slp->id,
            'typ_move' => 3,
            'qty' => 5,
            'user_id' => 1,
        ]);

        Queue::assertPushed(PushSheetLotToN2P::class, function (PushSheetLotToN2P $job) {
            $lot = $job->payload['lots'][0] ?? [];
            return ($lot['material'] ?? null) === 'S235JR'
                && (float) ($lot['thickness'] ?? 0) === 3.0
                && (float) ($lot['sheet_x'] ?? 0) === 1250.0
                && (int) ($lot['initial_qty'] ?? 0) === 5
                && str_starts_with($lot['external_ref'] ?? '', 'MV-');
        });
    }

    public function test_non_sheet_metal_service_does_not_dispatch(): void
    {
        Queue::fake();

        $slp = $this->makeSlp(serviceType: 5, unitCode: 'PC');

        StockMove::create([
            'stock_location_products_id' => $slp->id,
            'typ_move' => 3,
            'qty' => 5,
            'user_id' => 1,
        ]);

        Queue::assertNotPushed(PushSheetLotToN2P::class);
    }

    public function test_non_reception_typ_move_does_not_dispatch(): void
    {
        Queue::fake();

        $slp = $this->makeSlp(serviceType: 3, unitCode: 'PC');

        StockMove::create([
            'stock_location_products_id' => $slp->id,
            'typ_move' => 2,
            'qty' => 3,
            'user_id' => 1,
        ]);

        Queue::assertNotPushed(PushSheetLotToN2P::class);
    }

    public function test_wrong_unit_does_not_dispatch(): void
    {
        Queue::fake();

        $slp = $this->makeSlp(serviceType: 3, unitCode: 'KG');

        StockMove::create([
            'stock_location_products_id' => $slp->id,
            'typ_move' => 3,
            'qty' => 100,
            'user_id' => 1,
        ]);

        Queue::assertNotPushed(PushSheetLotToN2P::class);
    }

    public function test_disabled_endpoint_does_not_dispatch(): void
    {
        Queue::fake();
        IntegrationEndpoint::query()->update(['is_active' => false]);

        $slp = $this->makeSlp(serviceType: 3, unitCode: 'PC');

        StockMove::create([
            'stock_location_products_id' => $slp->id,
            'typ_move' => 3,
            'qty' => 5,
            'user_id' => 1,
        ]);

        Queue::assertNotPushed(PushSheetLotToN2P::class);
    }

    private function makeSlp(int $serviceType, string $unitCode, array $productOverrides = []): StockLocationProducts
    {
        // StockLocationProductsFactory tire StockLocation::inRandomOrder()->first()
        // sans fallback : sans seed préalable, ->id crashe sur null.
        StockLocation::query()->exists() || StockLocation::factory()->create();

        $service = MethodsServices::factory()->create(['type' => $serviceType]);
        $family = MethodsFamilies::factory()->create(['methods_services_id' => $service->id]);
        $unit = MethodsUnits::factory()->create(['code' => $unitCode]);

        $product = Products::factory()->create(array_merge([
            'methods_families_id' => $family->id,
            'methods_units_id'    => $unit->id,
        ], $productOverrides));

        return StockLocationProducts::factory()->create([
            'products_id' => $product->id,
        ]);
    }
}
