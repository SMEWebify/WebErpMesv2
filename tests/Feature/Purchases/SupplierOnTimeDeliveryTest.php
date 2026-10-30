<?php

namespace Tests\Feature\Purchases;

use App\Models\Companies\Companies;
use App\Models\Purchases\PurchaseLines;
use App\Models\Purchases\PurchaseReceipt;
use App\Models\Purchases\PurchaseReceiptLines;
use App\Models\Purchases\Purchases;
use App\Models\User;
use App\Services\PurchaseKPIService;
use App\Services\Purchases\SupplierOnTimeDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Taux de livraison à l'heure des fournisseurs : date confirmée sinon demandée, dernière
 * réception de la ligne, tolérance, fenêtre glissante de 12 mois.
 */
class SupplierOnTimeDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Companies $supplier;
    private Purchases $purchase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->supplier = Companies::factory()->create(['statu_supplier' => 2]);
        $this->purchase = Purchases::factory()->create(['companies_id' => $this->supplier->id]);
        config(['purchases.on_time_tolerance_days' => 0, 'supplier_rating.window_months' => 12]);
    }

    private function line(array $attributes = [], ?Purchases $purchase = null): PurchaseLines
    {
        return PurchaseLines::create($attributes + [
            'purchases_id' => ($purchase ?? $this->purchase)->id, 'tasks_id' => 0, 'ordre' => 1, 'code' => '',
            'label' => 'Tôle', 'qty' => 4, 'receipt_qty' => 0, 'selling_price' => 100, 'discount' => 0,
            'unit_price_after_discount' => 0, 'total_selling_price' => 0,
        ]);
    }

    private function receive(PurchaseLines $line, Carbon $at, ?float $qty = null): PurchaseReceipt
    {
        $receipt = PurchaseReceipt::create([
            'code' => 'REC-' . uniqid(), 'label' => 'Réception',
            'companies_id' => $line->purchase->companies_id, 'user_id' => $this->user->id,
        ]);
        DB::table('purchase_receipts')->where('id', $receipt->id)->update(['created_at' => $at, 'updated_at' => $at]);

        PurchaseReceiptLines::create([
            'purchase_receipt_id' => $receipt->id, 'purchase_line_id' => $line->id,
            'ordre' => 10, 'receipt_qty' => $qty ?? $line->qty,
        ]);

        return $receipt;
    }

    private function stats(): array
    {
        return app(SupplierOnTimeDelivery::class)->forSupplier($this->supplier->id);
    }

    /** @test */
    public function a_line_received_after_the_requested_date_is_late()
    {
        $this->receive($this->line(['delivery_date' => now()->subDays(10)->toDateString()]), now()->subDays(5));

        $stats = $this->stats();
        $this->assertSame(0, $stats['on_time']);
        $this->assertSame(1, $stats['late']);
        $this->assertEquals(0.0, $stats['rate']);
    }

    /** @test */
    public function the_confirmed_date_prevails_over_the_requested_date()
    {
        // Demandée il y a 10 jours, confirmée par le fournisseur à J-3, reçue à J-5 : à l'heure.
        $this->receive($this->line([
            'delivery_date'           => now()->subDays(10)->toDateString(),
            'confirmed_delivery_date' => now()->subDays(3)->toDateString(),
        ]), now()->subDays(5));

        // Confirmée plus tôt que demandée : c'est la date confirmée qui fait foi, donc en retard.
        $this->receive($this->line([
            'delivery_date'           => now()->subDays(2)->toDateString(),
            'confirmed_delivery_date' => now()->subDays(8)->toDateString(),
        ]), now()->subDays(5));

        $stats = $this->stats();
        $this->assertSame(1, $stats['on_time']);
        $this->assertSame(1, $stats['late']);
        $this->assertEquals(50.0, $stats['rate']);
    }

    /** @test */
    public function a_line_received_on_the_reference_day_is_on_time()
    {
        $day = now()->subDays(5);
        $this->receive($this->line(['delivery_date' => $day->toDateString()]), $day->copy()->setTime(17, 30));

        $this->assertSame(1, $this->stats()['on_time']);
    }

    /** @test */
    public function the_tolerance_extends_the_deadline()
    {
        // Reçue 2 jours après la date demandée.
        $this->receive($this->line(['delivery_date' => now()->subDays(7)->toDateString()]), now()->subDays(5));

        config(['purchases.on_time_tolerance_days' => 1]);
        $this->assertSame(1, $this->stats()['late']);

        config(['purchases.on_time_tolerance_days' => 2]);
        $stats = $this->stats();
        $this->assertSame(1, $stats['on_time']);
        $this->assertSame(2, $stats['tolerance_days']);
    }

    /** @test */
    public function a_partial_delivery_counts_at_the_date_of_the_last_receipt()
    {
        $line = $this->line(['delivery_date' => now()->subDays(10)->toDateString()]);
        $this->receive($line, now()->subDays(12), 2); // première moitié en avance
        $this->receive($line, now()->subDays(4), 2);  // solde en retard

        // Ligne encore incomplète : pas encore mesurée.
        $open = $this->line(['delivery_date' => now()->subDays(10)->toDateString()]);
        $this->receive($open, now()->subDays(12), 1);

        $stats = $this->stats();
        $this->assertSame(1, $stats['measured']);
        $this->assertSame(1, $stats['late']);
    }

    /** @test */
    public function only_the_last_12_months_and_dated_lines_count()
    {
        $this->receive($this->line(['delivery_date' => now()->subMonths(14)->toDateString()]), now()->subMonths(13));
        $this->receive($this->line(), now()->subDays(3));
        $this->receive($this->line(['delivery_date' => now()->toDateString()]), now()->subDays(3));

        $stats = $this->stats();
        $this->assertSame(1, $stats['measured']);
        $this->assertSame(1, $stats['on_time']);
        $this->assertSame(1, $stats['undated']);
        $this->assertEquals(100.0, $stats['rate']);
    }

    /** @test */
    public function a_deleted_receipt_is_ignored()
    {
        $receipt = $this->receive($this->line(['delivery_date' => now()->subDays(10)->toDateString()]), now()->subDays(5));
        $receipt->delete();

        $this->assertSame(0, $this->stats()['measured']);
        $this->assertNull($this->stats()['rate']);
    }

    /** @test */
    public function the_purchase_indicator_gives_the_global_rate_and_the_least_punctual_suppliers()
    {
        $other = Companies::factory()->create(['statu_supplier' => 2]);
        $otherPurchase = Purchases::factory()->create(['companies_id' => $other->id]);

        $this->receive($this->line(['delivery_date' => now()->toDateString()]), now()->subDays(3));
        $this->receive($this->line(['delivery_date' => now()->subDays(10)->toDateString()], $otherPurchase), now()->subDays(3));

        $queries = 0;
        DB::listen(function () use (&$queries) { $queries++; });
        $indicator = app(PurchaseKPIService::class)->getSupplierOnTimeDelivery();

        $this->assertSame(2, $queries); // taux + libellés, quel que soit le nombre de fournisseurs
        $this->assertSame(2, $indicator['global']['measured']);
        $this->assertEquals(50.0, $indicator['global']['rate']);
        $this->assertSame([$other->id, $this->supplier->id], $indicator['suppliers']->pluck('id')->all());
        $this->assertSame($other->label, $indicator['suppliers']->first()['label']);
    }

    /** @test */
    public function on_time_is_judged_by_calendar_day()
    {
        $this->assertTrue(SupplierOnTimeDelivery::isOnTime('2026-10-20 23:59:00', '2026-10-20'));
        $this->assertFalse(SupplierOnTimeDelivery::isOnTime('2026-10-21 00:01:00', '2026-10-20'));
        $this->assertTrue(SupplierOnTimeDelivery::isOnTime('2026-10-23 08:00:00', '2026-10-20', 3));
        $this->assertSame('text-bg-danger', SupplierOnTimeDelivery::badgeClass(50.0));
        $this->assertSame('text-bg-secondary', SupplierOnTimeDelivery::badgeClass(null));
    }
}
