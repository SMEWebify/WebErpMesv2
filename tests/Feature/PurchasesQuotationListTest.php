<?php

namespace Tests\Feature;

use App\Models\Purchases\PurchasesQuotation;
use App\Models\User;
use App\Models\Workflow\Orders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchasesQuotationListTest extends TestCase
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
        $user = User::factory()->create();
        $this->actingAs($user);

        // La commande apporte une société, un contact et une adresse cohérents.
        $order = Orders::factory()->create();
        foreach ([1, 2, 6] as $statu) {
            PurchasesQuotation::query()->insert([
                'code'                   => "DP-{$statu}",
                'label'                  => "Demande {$statu}",
                'companies_id'           => $order->companies_id,
                'companies_contacts_id'  => $order->companies_contacts_id,
                'companies_addresses_id' => $order->companies_addresses_id,
                'statu'                  => $statu,
                'user_id'                => $user->id,
                'created_at'             => '2026-05-01 10:00:00',
                'updated_at'             => '2026-05-01 10:00:00',
            ]);
        }
    }

    private function list(array $query): array
    {
        return $this->getJson(route('purchases.quotation.api.list', $query))->assertOk()->json('data');
    }

    public function test_without_filter_lists_every_status(): void
    {
        $this->assertCount(3, $this->list([]));
    }

    public function test_statuses_filters_on_several_values(): void
    {
        $codes = collect($this->list(['statuses' => [1, 6]]))->pluck('code')->sort()->values()->all();

        $this->assertSame(['DP-1', 'DP-6'], $codes);
    }

    public function test_rows_carry_a_comparable_creation_date(): void
    {
        $this->assertSame('2026-05-01', $this->list([])[0]['created_date']);
    }
}
