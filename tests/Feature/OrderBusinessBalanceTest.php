<?php

namespace Tests\Feature;

use App\Models\Accounting\AccountingVat;
use App\Models\User;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Number;
use Tests\TestCase;

/**
 * Bilan d'affaire de la fiche commande : montant TTC, marges sur le HT.
 */
class OrderBusinessBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_margins_are_computed_on_the_amount_excluding_vat(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);
        $this->actingAs(User::factory()->create());

        $vat   = AccountingVat::factory()->create(['rate' => 20]);
        $order = Orders::factory()->create(['statu' => 1, 'type' => 1]);
        OrderLines::factory()->create([
            'orders_id' => $order->id, 'qty' => 10, 'selling_price' => 100, 'discount' => 0,
            'accounting_vats_id' => $vat->id, 'use_calculated_price' => 0,
        ]);

        $html = $this->get(route('orders.show', ['id' => $order->id]))->assertOk()->getContent();
        $money = fn (float $v) => e(Number::currency($v, 'EUR', config('app.locale')));

        // Montant 1 200 TTC ; sans coût, les deux marges valent le HT (1 000), pas le TTC.
        preg_match_all('/business-balance-info__label">([^<]+)<\/p>\s*<p class="business-balance-info__value">\s*([^<]+?)\s*</', $html, $cards, PREG_SET_ORDER);
        $values = collect($cards)->mapWithKeys(fn ($c) => [trim(html_entity_decode($c[1])) => trim($c[2])]);

        $this->assertSame($money(1200), $values[__('general_content.amount_trans_key')]);
        $this->assertSame($money(1000), $values[html_entity_decode(__('general_content.forecast_margin_trans_key'))]);
        $this->assertSame($money(1000), $values[html_entity_decode(__('general_content.current_margin_trans_key'))]);
    }
}
