<?php

namespace Tests\Feature;

use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentConditions;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\User;
use App\Models\Workflow\DeliveryLines;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\OrderLineDelayHistory;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Services\OrderKPIService;
use App\Support\OrderLineDelayContext;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Historique des reports de date d'une ligne de commande, date promise figée
 * et taux de service mesuré sur la date promise.
 */
class OrderLineDelayHistoryTest extends TestCase
{
    private User $user;
    private Orders $order;

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
        $this->order = Orders::factory()->create(['statu' => 1, 'type' => 1, 'validity_date' => '2026-12-15']);
    }

    private function line(array $attributes = []): OrderLines
    {
        return OrderLines::factory()->create($attributes + [
            'orders_id'       => $this->order->id,
            'line_type'       => 'article',
            'qty'             => 2,
            'selling_price'   => 100,
            'discount'        => 0,
            'delivery_status' => 1,
            'delivery_date'   => '2026-12-15',
            'internal_delay'  => '2026-12-10',
        ]);
    }

    private function updatePayload(OrderLines $line, array $overrides = []): array
    {
        return $overrides + [
            'ordre'         => $line->ordre ?? 1,
            'label'         => $line->label,
            'qty'           => $line->qty,
            'selling_price' => 100,
            'discount'      => 0,
            'delivery_date' => '2026-12-15',
        ];
    }

    // ------------------------------------------------------------------
    // Historique
    // ------------------------------------------------------------------

    public function test_a_postponement_creates_a_history_row(): void
    {
        $line = $this->line();

        $this->putJson(route('orders.lines.json.update', ['orderId' => $this->order->id, 'id' => $line->id]),
            $this->updatePayload($line, ['delivery_date' => '2027-01-10', 'delay_reason' => 'Retard tôle fournisseur']))
            ->assertOk();

        $history = OrderLineDelayHistory::where('order_lines_id', $line->id)->get();
        $this->assertCount(1, $history);
        $this->assertSame('delivery_date', $history[0]->field);
        $this->assertSame('2026-12-15', substr((string) $history[0]->old_value, 0, 10));
        $this->assertSame('2027-01-10', substr((string) $history[0]->new_value, 0, 10));
        $this->assertSame('Retard tôle fournisseur', $history[0]->reason);
        $this->assertSame(OrderLineDelayHistory::ORIGIN_MANUAL, $history[0]->origin);
        $this->assertSame($this->user->id, (int) $history[0]->user_id);
    }

    public function test_the_reason_is_optional_and_never_blocks_the_save(): void
    {
        $line = $this->line();

        $this->putJson(route('orders.lines.json.update', ['orderId' => $this->order->id, 'id' => $line->id]),
            $this->updatePayload($line, ['delivery_date' => '2027-01-10', 'delay_reason' => str_repeat('x', 400)]))
            ->assertOk();
        $this->putJson(route('orders.lines.json.update', ['orderId' => $this->order->id, 'id' => $line->id]),
            $this->updatePayload($line, ['delivery_date' => '2027-02-01']))
            ->assertOk();

        $history = OrderLineDelayHistory::where('order_lines_id', $line->id)->orderBy('id')->get();
        $this->assertCount(2, $history);
        $this->assertSame(255, mb_strlen($history[0]->reason));
        $this->assertNull($history[1]->reason);
    }

    public function test_an_update_without_date_change_creates_no_history(): void
    {
        $line = $this->line();

        $this->putJson(route('orders.lines.json.update', ['orderId' => $this->order->id, 'id' => $line->id]),
            $this->updatePayload($line, ['label' => 'Nouveau libellé', 'qty' => 5]))
            ->assertOk();

        // Même jour écrit autrement : pas un report.
        $line->refresh()->update(['delivery_date' => '2026-12-15 00:00:00']);

        $this->assertSame(0, OrderLineDelayHistory::count());
        $this->assertSame('Nouveau libellé', $line->fresh()->label);
    }

    public function test_internal_delay_changes_are_recorded_too(): void
    {
        $line = $this->line();

        $line->update(['internal_delay' => '2026-12-20']);

        $this->assertDatabaseHas('order_line_delay_histories', [
            'order_lines_id' => $line->id,
            'field'          => 'internal_delay',
        ]);
    }

    public function test_presentation_lines_are_not_recorded(): void
    {
        $section = $this->line(['line_type' => 'section']);

        $section->update(['delivery_date' => '2027-01-10']);

        $this->assertSame(0, OrderLineDelayHistory::count());
    }

    public function test_applying_the_order_date_to_its_lines_is_recorded_per_line(): void
    {
        $first  = $this->line();
        $second = $this->line(['delivery_date' => '2026-12-20']);

        $this->post(route('orders.update', ['id' => $this->order->id]), [
            'label'                            => $this->order->label,
            'companies_id'                     => $this->order->companies_id,
            'companies_contacts_id'            => $this->order->companies_contacts_id,
            'companies_addresses_id'           => $this->order->companies_addresses_id,
            'accounting_payment_conditions_id' => AccountingPaymentConditions::factory()->create()->id,
            'accounting_payment_methods_id'    => AccountingPaymentMethod::factory()->create()->id,
            'accounting_deliveries_id'         => AccountingDelivery::factory()->create()->id,
            'validity_date'                    => '2027-03-01',
            'apply_delivery_date'              => 1,
        ])->assertRedirect();

        foreach ([$first, $second] as $line) {
            $this->assertSame('2027-03-01', substr((string) $line->fresh()->delivery_date, 0, 10));
            $this->assertDatabaseHas('order_line_delay_histories', [
                'order_lines_id' => $line->id,
                'field'          => 'delivery_date',
                'origin'         => OrderLineDelayHistory::ORIGIN_ORDER,
            ]);
        }
        // Date promise d'origine conservée malgré le report en masse.
        $this->assertSame('2026-12-20', substr((string) $second->fresh()->promised_delivery_date, 0, 10));
    }

    // ------------------------------------------------------------------
    // Date promise
    // ------------------------------------------------------------------

    public function test_the_promised_date_is_set_at_creation_and_never_moves(): void
    {
        $line = $this->line();
        $this->assertSame('2026-12-15', substr((string) $line->fresh()->promised_delivery_date, 0, 10));

        $line->update(['delivery_date' => '2027-01-10']);
        $line->update(['delivery_date' => '2027-02-01']);

        // Même une écriture directe de l'attribut est annulée.
        $line->refresh();
        $line->promised_delivery_date = '2030-01-01';
        $line->save();

        $this->assertSame('2026-12-15', substr((string) $line->fresh()->promised_delivery_date, 0, 10));
        $this->assertSame('2027-02-01', substr((string) $line->fresh()->delivery_date, 0, 10));
    }

    public function test_the_promised_date_cannot_be_mass_assigned_from_a_request(): void
    {
        $line = $this->line();

        $this->putJson(route('orders.lines.json.update', ['orderId' => $this->order->id, 'id' => $line->id]),
            $this->updatePayload($line, ['promised_delivery_date' => '2030-01-01']))
            ->assertOk();

        $this->assertSame('2026-12-15', substr((string) $line->fresh()->promised_delivery_date, 0, 10));
    }

    public function test_saving_a_partially_loaded_line_keeps_its_promised_date(): void
    {
        $line = $this->line();
        $line->update(['delivery_date' => '2027-01-10']);

        $partial = OrderLines::select(['id', 'orders_id', 'label'])->findOrFail($line->id);
        $partial->update(['label' => 'Renommée']);

        $this->assertSame('2026-12-15', substr((string) $line->fresh()->promised_delivery_date, 0, 10));
        $this->assertSame(1, OrderLineDelayHistory::count());
    }

    public function test_a_line_created_without_date_gets_its_first_date_as_promise(): void
    {
        $line = $this->line(['delivery_date' => null]);
        $this->assertNull($line->fresh()->promised_delivery_date);

        $line->update(['delivery_date' => '2027-01-10']);
        $line->update(['delivery_date' => '2027-02-01']);

        $this->assertSame('2027-01-10', substr((string) $line->fresh()->promised_delivery_date, 0, 10));
    }

    public function test_a_legacy_line_keeps_the_date_in_force_before_its_first_postponement(): void
    {
        $line = $this->line();
        DB::table('order_lines')->where('id', $line->id)->update(['promised_delivery_date' => null]);

        $line->refresh()->update(['delivery_date' => '2027-01-10']);

        $this->assertSame('2026-12-15', substr((string) $line->fresh()->promised_delivery_date, 0, 10));
    }

    public function test_a_duplicated_line_is_promised_its_current_date(): void
    {
        $line = $this->line();
        $line->update(['delivery_date' => '2027-01-10']);

        $this->postJson(route('orders.lines.json.duplicate', ['orderId' => $this->order->id, 'id' => $line->id]))
            ->assertSuccessful();

        $copy = OrderLines::where('orders_id', $this->order->id)->where('id', '!=', $line->id)->firstOrFail();
        $this->assertSame('2027-01-10', substr((string) $copy->promised_delivery_date, 0, 10));
    }

    // ------------------------------------------------------------------
    // Reprise
    // ------------------------------------------------------------------

    public function test_backfill_uses_first_recorded_date_then_current_date_and_dry_run_writes_nothing(): void
    {
        $postponed = $this->line(['delivery_date' => '2026-11-01']);
        $postponed->update(['delivery_date' => '2026-11-20']);
        $postponed->update(['delivery_date' => '2026-12-05']);
        $untouched = $this->line(['delivery_date' => '2026-12-24']);
        $undated   = $this->line(['delivery_date' => null]);
        $kept      = $this->line(['delivery_date' => '2026-10-01']);

        // Simule des lignes antérieures à la colonne (sauf $kept, déjà renseignée).
        DB::table('order_lines')->whereIn('id', [$postponed->id, $untouched->id, $undated->id])
            ->update(['promised_delivery_date' => null]);

        $this->artisan('wem:orders:backfill-promised-dates', ['--dry-run' => true])->assertSuccessful();
        $this->assertNull($postponed->fresh()->promised_delivery_date);

        $this->artisan('wem:orders:backfill-promised-dates')->assertSuccessful();

        $this->assertSame('2026-11-01', substr((string) $postponed->fresh()->promised_delivery_date, 0, 10));
        $this->assertSame('2026-12-24', substr((string) $untouched->fresh()->promised_delivery_date, 0, 10));
        $this->assertNull($undated->fresh()->promised_delivery_date);
        $this->assertSame('2026-10-01', substr((string) $kept->fresh()->promised_delivery_date, 0, 10));
    }

    // ------------------------------------------------------------------
    // Taux de service
    // ------------------------------------------------------------------

    private function deliver(OrderLines $line, string $at): void
    {
        DB::table('order_lines')->where('id', $line->id)->update(['delivery_status' => 3]);
        $delivery = Deliverys::factory()->create();
        $deliveryLine = DeliveryLines::create([
            'deliverys_id'   => $delivery->id,
            'order_line_id'  => $line->id,
            'ordre'          => 10,
            'qty'            => $line->qty,
            'invoice_status' => 1,
        ]);
        DB::table('delivery_lines')->where('id', $deliveryLine->id)->update(['created_at' => $at]);
    }

    public function test_the_service_rate_against_the_promised_date_is_exact(): void
    {
        // Promise le 1er octobre, reportée au 20, livrée le 15 : à l'heure sur la
        // date actuelle, en retard sur la date promise.
        $postponed = $this->line(['delivery_date' => '2026-10-01']);
        $postponed->update(['delivery_date' => '2026-10-20']);
        $this->deliver($postponed, '2026-10-15 09:00:00');

        // Promise et tenue : à l'heure sur les deux mesures.
        $kept = $this->line(['delivery_date' => '2026-10-10']);
        $this->deliver($kept, '2026-10-05 09:00:00');

        // Ligne ancienne sans date promise : retombe sur la date actuelle (en retard).
        $legacy = $this->line(['delivery_date' => '2026-10-10']);
        DB::table('order_lines')->where('id', $legacy->id)->update(['promised_delivery_date' => null]);
        $this->deliver($legacy, '2026-10-12 09:00:00');

        // Non livrée : hors calcul.
        $this->line(['delivery_date' => '2026-09-01']);

        $promised = app(OrderKPIService::class)->getPromisedDateServiceRate();
        $this->assertSame(['rate' => 33.33, 'onTime' => 1, 'total' => 3, 'fallback' => 1], $promised);

        // La mesure historique est servie inchangée, à côté de la nouvelle.
        $this->getJson(route('kpi.otd'))
            ->assertOk()
            ->assertJson([
                'rate'     => 66.67,
                'onTime'   => 2,
                'total'    => 3,
                'promised' => ['rate' => 33.33, 'onTime' => 1, 'total' => 3, 'fallback' => 1],
            ]);
    }

    public function test_the_delay_history_endpoint_lists_postponements_newest_first(): void
    {
        $line = $this->line();
        OrderLineDelayContext::run(OrderLineDelayHistory::ORIGIN_MANUAL,
            fn () => $line->update(['delivery_date' => '2027-01-10']), 'Client absent');
        $line->update(['delivery_date' => '2027-02-01']);

        $this->getJson(route('orders.lines.json.delay-history', ['orderId' => $this->order->id, 'id' => $line->id]))
            ->assertOk()
            ->assertJsonPath('promised_delivery_date', '2026-12-15')
            ->assertJsonCount(2, 'history')
            ->assertJsonPath('history.0.new_value', '2027-02-01')
            ->assertJsonPath('history.1.reason', 'Client absent')
            ->assertJsonPath('history.1.user_name', $this->user->name);
    }
}
