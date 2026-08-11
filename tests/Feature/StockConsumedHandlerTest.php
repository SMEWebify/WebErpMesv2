<?php

namespace Tests\Feature;

use App\Models\Integrations\IntegrationEndpoint;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsServices;
use App\Models\Methods\MethodsUnits;
use App\Models\Products\Products;
use App\Models\Products\StockLocation;
use App\Models\Products\StockLocationProducts;
use App\Models\Products\StockMove;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class StockConsumedHandlerTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'stock-inbound-secret';

    private IntegrationEndpoint $endpoint;

    protected function setUp(): void
    {
        parent::setUp();

        IntegrationEndpoint::query()->where('system_code', 'n2p')->delete();
        $this->endpoint = IntegrationEndpoint::create([
            'name'                  => 'N2P inbound test',
            'system_code'           => 'n2p',
            'direction'             => IntegrationEndpoint::DIRECTION_INBOUND,
            'auth_method'           => IntegrationEndpoint::AUTH_HMAC,
            'hmac_secret'           => self::SECRET,
            'hmac_header'           => 'X-N2P-Signature-256',
            'timestamp_header'      => 'X-N2P-Timestamp',
            'timestamp_tolerance_s' => 300,
            'is_active'             => true,
            'events'                => [],
            'verify_ssl'            => true,
        ]);
    }

    public function test_stock_consumed_creates_consumption_move_on_original_slp(): void
    {
        $slp = $this->makeSheetSlp();
        $originalMove = StockMove::create([
            'stock_location_products_id' => $slp->id,
            'typ_move' => 3,
            'qty' => 5,
            'user_id' => 1,
            'component_price' => 42.5,
        ]);

        $body = $this->buildBody('stock.consumed', [
            'stock_lot_external_ref' => 'MV-' . $originalMove->id,
            'qty' => 2,
            'consumed_by' => ['nest_id' => 77, 'nest_name' => 'NEST-2026-042'],
        ]);

        $this->signedPost('n2p', $body)->assertStatus(200);

        $this->assertDatabaseHas('stock_moves', [
            'stock_location_products_id' => $slp->id,
            'typ_move' => 2,
            'qty' => 2,
        ]);

        $consumptionMove = StockMove::query()
            ->where('typ_move', 2)
            ->where('stock_location_products_id', $slp->id)
            ->first();
        $this->assertNotNull($consumptionMove);
        $this->assertStringContainsString('NEST-2026-042', (string) $consumptionMove->tracability);
    }

    public function test_unknown_external_ref_marks_delivery_failed(): void
    {
        $body = $this->buildBody('stock.consumed', [
            'stock_lot_external_ref' => 'MV-999999',
            'qty' => 1,
        ]);

        $this->signedPost('n2p', $body)->assertStatus(500)->assertJson(['status' => 'failed']);

        $this->assertDatabaseMissing('stock_moves', ['typ_move' => 2]);
    }

    public function test_zero_qty_rejected_and_no_move_created(): void
    {
        $slp = $this->makeSheetSlp();
        $originalMove = StockMove::create([
            'stock_location_products_id' => $slp->id,
            'typ_move' => 3, 'qty' => 5, 'user_id' => 1,
        ]);

        $body = $this->buildBody('stock.consumed', [
            'stock_lot_external_ref' => 'MV-' . $originalMove->id,
            'qty' => 0,
        ]);

        $this->signedPost('n2p', $body)->assertStatus(500)->assertJson(['status' => 'failed']);

        $this->assertDatabaseMissing('stock_moves', [
            'stock_location_products_id' => $slp->id,
            'typ_move' => 2,
        ]);
    }

    private function makeSheetSlp(): StockLocationProducts
    {
        // StockLocationProductsFactory tire StockLocation::inRandomOrder()->first()
        // sans fallback : sans seed préalable, ->id crashe sur null.
        StockLocation::query()->exists() || StockLocation::factory()->create();

        $service = MethodsServices::factory()->create(['type' => 3]);
        $family = MethodsFamilies::factory()->create(['methods_services_id' => $service->id]);
        $unit = MethodsUnits::factory()->create(['code' => 'PC']);
        $product = Products::factory()->create([
            'methods_families_id' => $family->id,
            'methods_units_id' => $unit->id,
        ]);

        return StockLocationProducts::factory()->create(['products_id' => $product->id]);
    }

    private function buildBody(string $eventType, array $data = []): string
    {
        return json_encode([
            'event_id'    => (string) Str::uuid(),
            'event_type'  => $eventType,
            'occurred_at' => Carbon::now()->toIso8601String(),
            'source'      => 'n2p',
            'data'        => $data,
        ]);
    }

    private function signedPost(string $systemCode, string $body): TestResponse
    {
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp . '.' . $body, self::SECRET);

        return $this->call(
            'POST',
            '/api/integrations/' . $systemCode . '/inbound',
            [], [], [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_N2P_TIMESTAMP' => (string) $timestamp,
                'HTTP_X_N2P_SIGNATURE_256' => $signature,
            ],
            $body
        );
    }
}
