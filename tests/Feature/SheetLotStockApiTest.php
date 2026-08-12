<?php

namespace Tests\Feature;

use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsServices;
use App\Models\Methods\MethodsUnits;
use App\Models\Products\Products;
use App\Models\Products\StockLocationProducts;
use App\Models\Products\StockMove;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SheetLotStockApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_current_stock_for_valid_ref(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $service = MethodsServices::factory()->create(['type' => 3]);
        $family = MethodsFamilies::factory()->create(['methods_services_id' => $service->id]);
        $unit = MethodsUnits::factory()->create(['code' => 'PC']);
        $product = Products::factory()->create([
            'methods_families_id' => $family->id,
            'methods_units_id'    => $unit->id,
            'code'                => 'TOL-S235-3-1250x2500',
            'label'               => 'Tôle S235 3mm 1250x2500',
            'material'            => 'S235JR',
            'thickness'           => 3,
            'x_size'              => 1250,
            'y_size'              => 2500,
        ]);
        $slp = StockLocationProducts::factory()->create(['products_id' => $product->id]);

        $originalMove = StockMove::create([
            'stock_location_products_id' => $slp->id,
            'typ_move' => 3,
            'qty' => 10,
            'user_id' => 1,
        ]);

        $response = $this->getJson('/api/n2p/sheet-lots/MV-' . $originalMove->id . '/stock');

        $response->assertOk()->assertJson([
            'external_ref' => 'MV-' . $originalMove->id,
            'product' => [
                'code'      => 'TOL-S235-3-1250x2500',
                'material'  => 'S235JR',
                'thickness' => '3.000',
                'sheet_x'   => '1250.000',
                'sheet_y'   => '2500.000',
            ],
        ]);
        $this->assertArrayHasKey('current_qty', $response->json());
    }

    public function test_invalid_ref_prefix_returns_400(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $this->getJson('/api/n2p/sheet-lots/XX-42/stock')
            ->assertStatus(400)
            ->assertJson(['error' => 'invalid_ref_format']);
    }

    public function test_unknown_ref_returns_404(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $this->getJson('/api/n2p/sheet-lots/MV-999999/stock')
            ->assertStatus(404)
            ->assertJson(['error' => 'ref_not_found']);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/n2p/sheet-lots/MV-1/stock')->assertStatus(401);
    }
}
