<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workflow\Returns;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReturnsListTest extends TestCase
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

        foreach ([1, 2, 3, 4] as $statu) {
            Returns::create(['code' => "RET-{$statu}", 'label' => "Retour {$statu}", 'statu' => $statu]);
        }
    }

    private function codes(array $query): array
    {
        return collect($this->getJson(route('returns.json.list', $query))->assertOk()->json('data'))
            ->pluck('code')->sort()->values()->all();
    }

    public function test_without_filter_lists_every_status(): void
    {
        $this->assertSame(['RET-1', 'RET-2', 'RET-3', 'RET-4'], $this->codes([]));
    }

    public function test_statuses_filters_on_several_values(): void
    {
        $this->assertSame(['RET-1', 'RET-3'], $this->codes(['statuses' => [1, 3]]));
    }

    public function test_single_status_parameter_is_still_accepted(): void
    {
        $this->assertSame(['RET-2'], $this->codes(['status' => 2]));
    }
}
