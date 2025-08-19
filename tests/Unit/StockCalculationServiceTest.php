<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\StockCalculationService;
use App\Models\Products\StockLocationProducts;
use App\Models\Products\StockMove;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StockCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculates_weighted_average_cost(): void
    {
        $slp = StockLocationProducts::create([
            'code' => 'SLP1',
            'user_id' => 1,
            'stock_locations_id' => 1,
            'products_id' => 1,
        ]);

        // Input moves that should be considered in the average
        $slp->StockMove()->create([
            'user_id' => 1,
            'qty' => 10,
            'typ_move' => 1,
            'component_price' => 5,
        ]);

        $slp->StockMove()->create([
            'user_id' => 1,
            'qty' => 5,
            'typ_move' => 5,
            'component_price' => 10,
        ]);

        // Output move that should be ignored
        $slp->StockMove()->create([
            'user_id' => 1,
            'qty' => 8,
            'typ_move' => 2,
            'component_price' => 20,
        ]);

        $service = new StockCalculationService(new StockLocationProducts());

        $average = $service->calculateWeightedAverageCost($slp->id);

        $this->assertEqualsWithDelta(100 / 15, $average, 0.0001);
    }

    public function test_returns_zero_when_no_input_moves(): void
    {
        $slp = StockLocationProducts::create([
            'code' => 'SLP1',
            'user_id' => 1,
            'stock_locations_id' => 1,
            'products_id' => 1,
        ]);

        // Only an output move exists
        $slp->StockMove()->create([
            'user_id' => 1,
            'qty' => 8,
            'typ_move' => 2,
            'component_price' => 20,
        ]);

        $service = new StockCalculationService(new StockLocationProducts());

        $average = $service->calculateWeightedAverageCost($slp->id);

        $this->assertSame(0.0, $average);
    }
}
