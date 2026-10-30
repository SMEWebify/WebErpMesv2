<?php

namespace Tests\Feature\Purchases;

use App\Models\Companies\Companies;
use App\Models\Purchases\PurchaseLines;
use App\Models\Purchases\PurchaseReceipt;
use App\Models\Purchases\PurchaseReceiptLines;
use App\Models\Purchases\Purchases;
use App\Models\User;
use App\Services\Purchases\SupplierAcknowledgement;
use App\Services\Purchases\SupplierReceiptRating;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Accusé de réception fournisseur : saisie sur la fiche commande, liste des commandes
 * sans AR, date confirmée comme date de référence des retards.
 */
class SupplierAcknowledgementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Companies $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);
        Carbon::setTestNow('2026-10-30 10:00:00');

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->supplier = Companies::factory()->create(['statu_supplier' => 2]);
        config(['purchases.ack_overdue_days' => 3]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function purchase(int $statu = 2, ?string $orderedAt = '2026-10-20 09:00:00'): Purchases
    {
        $purchase = Purchases::factory()->create(['companies_id' => $this->supplier->id, 'statu' => $statu]);
        DB::table('purchases')->where('id', $purchase->id)->update(['ordered_at' => $orderedAt]);

        return $purchase->fresh();
    }

    private function line(Purchases $purchase, array $attributes = []): PurchaseLines
    {
        return PurchaseLines::create($attributes + [
            'purchases_id' => $purchase->id, 'tasks_id' => 0, 'ordre' => 1, 'code' => '',
            'label' => 'Tôle 3 mm', 'qty' => 4, 'receipt_qty' => 0, 'selling_price' => 100, 'discount' => 0,
            'unit_price_after_discount' => 100, 'total_selling_price' => 400,
            'delivery_date' => '2026-11-10',
        ]);
    }

    private function acknowledge(Purchases $purchase, array $lines, array $extra = [])
    {
        return $this->postJson(route('purchases.lines.json.acknowledge', ['purchaseId' => $purchase->id]), $extra + [
            'ack_at'    => '2026-10-29',
            'reference' => 'AR-4512',
            'lines'     => $lines,
        ]);
    }

    // ------------------------------------------------------------------
    // Saisie
    // ------------------------------------------------------------------

    /** @test */
    public function the_acknowledgement_is_recorded_with_the_requested_date_by_default()
    {
        $purchase = $this->purchase();
        $first = $this->line($purchase);
        $second = $this->line($purchase, ['ordre' => 2, 'delivery_date' => '2026-11-12']);

        $this->acknowledge($purchase, [
            ['id' => $first->id, 'confirmed_delivery_date' => '2026-11-17'],
            ['id' => $second->id],
        ])
            ->assertOk()
            ->assertJsonPath('lines.0.confirmed_delivery_date', '2026-11-17')
            ->assertJsonPath('lines.0.ack_gap_days', 7)
            ->assertJsonPath('lines.0.reference_delivery_date', '2026-11-17')
            ->assertJsonPath('lines.1.confirmed_delivery_date', '2026-11-12')
            ->assertJsonPath('lines.1.ack_gap_days', 0);

        $first->refresh();
        $this->assertSame('2026-10-29', substr($first->supplier_ack_at, 0, 10));
        $this->assertSame('AR-4512', $first->supplier_ack_reference);
        $this->assertSame('2026-11-17', substr($first->confirmed_delivery_date, 0, 10));
        // La date demandée n'est jamais touchée
        $this->assertSame('2026-11-10', substr($first->delivery_date, 0, 10));
    }

    /** @test */
    public function a_new_acknowledgement_replaces_the_previous_one()
    {
        $purchase = $this->purchase();
        $line = $this->line($purchase);

        $this->acknowledge($purchase, [['id' => $line->id, 'confirmed_delivery_date' => '2026-11-17']])->assertOk();
        $this->acknowledge($purchase, [['id' => $line->id, 'confirmed_delivery_date' => '2026-11-24']], [
            'ack_at' => '2026-10-30', 'reference' => 'AR-4512-B',
        ])->assertOk();

        $line->refresh();
        $this->assertSame('2026-11-24', substr($line->confirmed_delivery_date, 0, 10));
        $this->assertSame('AR-4512-B', $line->supplier_ack_reference);
    }

    /** @test */
    public function an_acknowledgement_is_refused_on_an_order_in_progress_or_canceled()
    {
        foreach ([1, 5] as $statu) {
            $purchase = $this->purchase($statu);
            $line = $this->line($purchase);

            $this->acknowledge($purchase, [['id' => $line->id]])
                ->assertStatus(422)
                ->assertJsonValidationErrors('purchase');

            $this->assertNull($line->fresh()->supplier_ack_at);
        }
    }

    /** @test */
    public function a_line_of_another_order_is_refused_and_nothing_is_written()
    {
        $purchase = $this->purchase();
        $own = $this->line($purchase);
        $foreign = $this->line($this->purchase());

        $this->acknowledge($purchase, [['id' => $own->id], ['id' => $foreign->id]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('lines');

        $this->assertNull($own->fresh()->supplier_ack_at);
        $this->assertNull($foreign->fresh()->supplier_ack_at);
    }

    /** @test */
    public function a_duplicated_line_waits_for_its_own_acknowledgement()
    {
        $purchase = $this->purchase(1, null);
        $line = $this->line($purchase);
        DB::table('purchase_lines')->where('id', $line->id)->update([
            'supplier_ack_at' => '2026-10-29', 'supplier_ack_reference' => 'AR-1', 'confirmed_delivery_date' => '2026-11-17',
        ]);

        $copy = $this->postJson(route('purchases.lines.json.duplicate', ['purchaseId' => $purchase->id, 'id' => $line->id]))
            ->assertCreated()
            ->json('line');

        $this->assertNull($copy['supplier_ack_at']);
        $this->assertNull($copy['confirmed_delivery_date']);
    }

    // ------------------------------------------------------------------
    // Date de commande
    // ------------------------------------------------------------------

    /** @test */
    public function placing_the_order_dates_it_and_going_back_in_progress_clears_it()
    {
        $purchase = Purchases::factory()->create(['companies_id' => $this->supplier->id, 'statu' => 1]);
        $this->assertNull($purchase->ordered_at);

        $purchase->update(['statu' => 2]);
        $this->assertSame('2026-10-30 10:00:00', $purchase->fresh()->ordered_at->toDateTimeString());

        $purchase->update(['statu' => 1]);
        $this->assertNull($purchase->fresh()->ordered_at);
    }

    // ------------------------------------------------------------------
    // Liste « sans AR »
    // ------------------------------------------------------------------

    /** @test */
    public function the_list_holds_placed_orders_waiting_for_an_acknowledgement_for_n_days()
    {
        $late = $this->purchase(2, '2026-10-26 09:00:00');                 // 4 jours
        $this->line($late);
        $partlyAcknowledged = $this->purchase(3, '2026-10-20 09:00:00');   // une ligne sur deux
        $this->line($partlyAcknowledged);
        $acknowledgedLine = $this->line($partlyAcknowledged, ['ordre' => 2]);
        DB::table('purchase_lines')->where('id', $acknowledgedLine->id)->update(['supplier_ack_at' => '2026-10-21']);

        $recent = $this->purchase(2, '2026-10-28 09:00:00');               // 2 jours : pas encore
        $this->line($recent);
        $acknowledged = $this->purchase(2, '2026-10-01 09:00:00');
        $done = $this->line($acknowledged);
        DB::table('purchase_lines')->where('id', $done->id)->update(['supplier_ack_at' => '2026-10-02']);
        $draft = $this->purchase(1, null);
        $this->line($draft);
        $canceled = $this->purchase(5, '2026-10-01 09:00:00');
        $this->line($canceled);
        $received = $this->purchase(4, '2026-10-01 09:00:00');
        $this->line($received);
        $zeroQty = $this->purchase(2, '2026-10-01 09:00:00');
        $this->line($zeroQty, ['qty' => 0]);

        $service = app(SupplierAcknowledgement::class);
        $list = $service->overdueList();

        $this->assertSame([$partlyAcknowledged->id, $late->id], $list->pluck('id')->all());
        $this->assertSame(10, $list[0]['days']);
        $this->assertSame(1, $list[0]['awaiting_lines_count']);
        $this->assertSame(2, $list[0]['lines_count']);
        $this->assertSame(2, $service->overdueCount());

        // N vient de la configuration
        config(['purchases.ack_overdue_days' => 1]);
        $this->assertSame(3, $service->overdueCount());
    }

    /** @test */
    public function an_order_placed_before_the_feature_falls_back_on_its_creation_date()
    {
        $old = $this->purchase(2, null);
        DB::table('purchases')->where('id', $old->id)->update(['created_at' => '2026-10-01 09:00:00']);
        $this->line($old);

        $this->assertSame([$old->id], app(SupplierAcknowledgement::class)->overdueList()->pluck('id')->all());
    }

    /** @test */
    public function the_list_page_is_displayed()
    {
        $purchase = $this->purchase(2, '2026-10-20 09:00:00');
        $this->line($purchase);

        $this->get(route('purchases.acknowledgements.missing'))
            ->assertOk()
            ->assertSee($purchase->code)
            ->assertSee(__('supplier_ack.missing_title'));
    }

    // ------------------------------------------------------------------
    // Date de référence des retards
    // ------------------------------------------------------------------

    /** @test */
    public function the_confirmed_date_is_the_reference_date()
    {
        $this->assertSame('2026-11-17', SupplierAcknowledgement::referenceDate(
            (object) ['delivery_date' => '2026-11-10', 'confirmed_delivery_date' => '2026-11-17']
        ));
        $this->assertSame('2026-11-10', SupplierAcknowledgement::referenceDate(
            (object) ['delivery_date' => '2026-11-10', 'confirmed_delivery_date' => null]
        ));
        $this->assertNull(SupplierAcknowledgement::referenceDate((object) ['delivery_date' => null, 'confirmed_delivery_date' => null]));
        $this->assertSame(-3, SupplierAcknowledgement::gapDays(
            (object) ['delivery_date' => '2026-11-10', 'confirmed_delivery_date' => '2026-11-07']
        ));
    }

    /** @test */
    public function the_receipt_delay_is_measured_against_the_confirmed_date()
    {
        $purchase = $this->purchase();
        $line = $this->line($purchase, ['delivery_date' => '2026-10-20', 'confirmed_delivery_date' => '2026-10-27']);

        $receipt = PurchaseReceipt::create([
            'code' => 'REC-1', 'label' => 'Réception', 'companies_id' => $this->supplier->id, 'user_id' => $this->user->id,
        ]);
        DB::table('purchase_receipts')->where('id', $receipt->id)->update(['created_at' => '2026-10-29 08:00:00']);
        PurchaseReceiptLines::create([
            'purchase_receipt_id' => $receipt->id, 'purchase_line_id' => $line->id, 'ordre' => 10, 'receipt_qty' => 4,
        ]);

        // 2 jours après la date confirmée (et non 9 après la date demandée)
        $this->assertSame(2, app(SupplierReceiptRating::class)->daysLate($receipt->fresh()));
    }

    /** @test */
    public function the_waiting_receipt_screen_shows_the_reference_date()
    {
        $purchase = $this->purchase();
        $this->line($purchase, ['delivery_date' => '2026-11-10', 'confirmed_delivery_date' => '2026-11-17']);

        $line = collect($this->getJson(route('purchases.waiting.receipt.json.init'))->assertOk()->json('lines'))
            ->firstWhere('purchases_id', $purchase->id);

        $this->assertSame('2026-11-17', $line['delivery_date']);
        $this->assertTrue($line['delivery_date_confirmed']);
    }
}
