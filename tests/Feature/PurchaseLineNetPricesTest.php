<?php

namespace Tests\Feature;

use App\Models\Accounting\AccountingVat;
use App\Models\Methods\MethodsUnits;
use App\Models\Purchases\PurchaseLines;
use App\Models\Purchases\Purchases;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * unit_price_after_discount / total_selling_price d'une ligne d'achat.
 *
 * L'écran React ne les renseignait pas : une ligne saisie y restait à 0, et
 * l'écran « En attente de facturation » la valorisait à 0 €.
 */
class PurchaseLineNetPricesTest extends TestCase
{
    use RefreshDatabase;

    private Purchases $purchase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);

        $this->actingAs(User::factory()->create());

        AccountingVat::create(['code' => 'TVA20', 'label' => 'TVA 20 %', 'rate' => 20, 'default' => 1]);
        MethodsUnits::create(['code' => 'KG', 'label' => 'Kilogramme', 'type' => 1, 'default' => 1]);

        $this->purchase = Purchases::factory()->create(['statu' => 1]);
    }

    public function test_a_line_entered_on_screen_stores_its_net_prices(): void
    {
        $this->postJson(route('purchases.lines.json.store', ['purchaseId' => $this->purchase->id]), [
            'ordre'         => 1,
            'label'         => 'Tôle DC01 2 mm',
            'qty'           => 1500,
            'selling_price' => 1.1,
            'discount'      => 10,
        ])->assertCreated();

        $line = PurchaseLines::firstOrFail();

        $this->assertEqualsWithDelta(0.99, (float) $line->unit_price_after_discount, 0.0001);
        $this->assertEqualsWithDelta(1485.0, (float) $line->total_selling_price, 0.0001);
    }

    public function test_editing_a_line_recomputes_its_net_prices(): void
    {
        $line = $this->makeLine(['qty' => 10, 'selling_price' => 5, 'discount' => 0]);

        $this->putJson(route('purchases.lines.json.update', ['purchaseId' => $this->purchase->id, 'id' => $line->id]), [
            'ordre'         => 1,
            'label'         => $line->label,
            'qty'           => 20,
            'selling_price' => 4,
            'discount'      => 25,
        ])->assertOk();

        $line->refresh();

        $this->assertEqualsWithDelta(3.0, (float) $line->unit_price_after_discount, 0.0001);
        $this->assertEqualsWithDelta(60.0, (float) $line->total_selling_price, 0.0001);
    }

    public function test_the_backfill_fixes_stale_lines_and_leaves_correct_ones_alone(): void
    {
        $stale   = $this->makeLine(['qty' => 3, 'selling_price' => 10, 'discount' => 50, 'unit_price_after_discount' => 0, 'total_selling_price' => 0]);
        $correct = $this->makeLine(['qty' => 2, 'selling_price' => 7, 'discount' => 0, 'unit_price_after_discount' => 7, 'total_selling_price' => 14]);
        $correctUpdatedAt = $correct->fresh()->updated_at;

        $this->artisan('wem:purchases:backfill-line-totals', ['--dry-run' => true])->assertSuccessful();
        $this->assertEqualsWithDelta(0.0, (float) $stale->fresh()->total_selling_price, 0.0001);

        $this->artisan('wem:purchases:backfill-line-totals')->assertSuccessful();

        $this->assertEqualsWithDelta(5.0, (float) $stale->fresh()->unit_price_after_discount, 0.0001);
        $this->assertEqualsWithDelta(15.0, (float) $stale->fresh()->total_selling_price, 0.0001);
        $this->assertEquals($correctUpdatedAt, $correct->fresh()->updated_at);
    }

    private function makeLine(array $attributes): PurchaseLines
    {
        return PurchaseLines::create($attributes + [
            'purchases_id'              => $this->purchase->id,
            'tasks_id'                  => 0,
            'ordre'                     => 1,
            'code'                      => '',
            'label'                     => 'Ligne',
            'unit_price_after_discount' => 0,
            'total_selling_price'       => 0,
        ]);
    }
}
