<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workflow\OrderConfirmations;
use App\Models\Workflow\Orders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderConfirmationsListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);
        $this->actingAs(User::factory()->create());

        $order = Orders::factory()->create();
        foreach ([1, 2, 3, 4] as $statu) {
            OrderConfirmations::query()->insert([
                'uuid'       => (string) Str::uuid(),
                'code'       => "ARC-{$statu}",
                'label'      => "Confirmation {$statu}",
                'order_id'   => $order->id,
                'statu'      => $statu,
                'revision'   => chr(64 + $statu),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function list(array $query): array
    {
        return $this->getJson(route('order.confirmations.json.list', $query))->assertOk()->json('data');
    }

    private function codes(array $query): array
    {
        return collect($this->list($query))->pluck('code')->sort()->values()->all();
    }

    public function test_statuses_filters_on_several_values(): void
    {
        $this->assertSame(['ARC-2', 'ARC-4'], $this->codes(['statuses' => [2, 4]]));
    }

    public function test_single_status_parameter_is_still_accepted(): void
    {
        $this->assertSame(['ARC-3'], $this->codes(['status' => 3]));
    }

    public function test_rows_carry_a_numeric_total_next_to_the_formatted_one(): void
    {
        $row = $this->list([])[0];

        $this->assertArrayHasKey('total', $row);
        $this->assertIsNumeric($row['total_amount']);
    }
}
