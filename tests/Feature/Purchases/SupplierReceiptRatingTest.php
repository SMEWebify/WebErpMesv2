<?php

namespace Tests\Feature\Purchases;

use App\Models\Companies\Companies;
use App\Models\Companies\SupplierRating;
use App\Models\Planning\Status;
use App\Models\Purchases\PurchaseLines;
use App\Models\Purchases\PurchaseReceipt;
use App\Models\Purchases\PurchaseReceiptLines;
use App\Models\Purchases\Purchases;
use App\Models\User;
use App\Http\Controllers\Purchases\PurchaseReceiptRatingController;
use App\Services\PurchaseKPIService;
use App\Services\Purchases\SupplierReceiptRating;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Notation rapide du fournisseur à la réception : enregistrement facultatif, délai proposé
 * d'après la date promise, note glissante 12 mois, poids des NC de réception.
 */
class SupplierReceiptRatingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Companies $supplier;
    private Purchases $purchase;

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

        $this->supplier = Companies::factory()->create(['statu_supplier' => 2]);
        $this->purchase = Purchases::factory()->create(['companies_id' => $this->supplier->id]);
    }

    private function line(array $attributes = []): PurchaseLines
    {
        return PurchaseLines::create($attributes + [
            'purchases_id' => $this->purchase->id, 'tasks_id' => 0, 'ordre' => 1, 'code' => '',
            'label' => 'Tôle', 'qty' => 4, 'receipt_qty' => 0, 'selling_price' => 100, 'discount' => 0,
            'unit_price_after_discount' => 0, 'total_selling_price' => 0,
        ]);
    }

    private function receipt(?PurchaseLines $line = null, ?Carbon $at = null): PurchaseReceipt
    {
        $receipt = PurchaseReceipt::create([
            'code' => 'REC-' . uniqid(), 'label' => 'Réception', 'companies_id' => $this->supplier->id,
            'user_id' => $this->user->id,
        ]);

        if ($at) {
            DB::table('purchase_receipts')->where('id', $receipt->id)->update(['created_at' => $at, 'updated_at' => $at]);
            $receipt->refresh();
        }

        $line ??= $this->line();
        PurchaseReceiptLines::create([
            'purchase_receipt_id' => $receipt->id, 'purchase_line_id' => $line->id,
            'ordre' => 10, 'receipt_qty' => $line->qty,
        ]);

        return $receipt;
    }

    private function attachNonConformity(PurchaseReceipt $receipt, int $statu = 1): void
    {
        $ncId = DB::table('quality_non_conformities')->insertGetId([
            'code' => 'NC-' . uniqid(), 'label' => 'Rayures', 'statu' => $statu, 'type' => 2,
            'user_id' => $this->user->id, 'companie_id' => $this->supplier->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        PurchaseReceiptLines::where('purchase_receipt_id', $receipt->id)->update(['quality_non_conformity_id' => $ncId]);
    }

    private function rate(PurchaseReceipt $receipt, int $conformity, int $delay, int $documents)
    {
        return $this->postJson(route('purchase.receipts.rating.store', ['receipt' => $receipt->id]), [
            'conformity' => $conformity, 'delay' => $delay, 'documents' => $documents, 'comment' => 'RAS',
        ]);
    }

    /** @test */
    public function the_quick_rating_is_stored_against_the_receipt()
    {
        $receipt = $this->receipt();

        $this->rate($receipt, 5, 3, 4)
            ->assertOk()
            ->assertJsonPath('state.rated', true)
            ->assertJsonPath('state.scores', ['conformity' => 5, 'delay' => 3, 'documents' => 4]);

        $this->assertDatabaseHas('supplier_ratings', [
            'purchase_receipt_id'        => $receipt->id,
            'purchases_id'               => $this->purchase->id,
            'companies_id'               => $this->supplier->id,
            'rating'                     => 4,
            'evaluation_score_quality'   => 100,
            'evaluation_score_logistics' => 60,
            'evaluation_score_service'   => 80,
            'comment'                    => 'RAS',
        ]);
    }

    /** @test */
    public function rating_a_receipt_again_corrects_its_rating()
    {
        $receipt = $this->receipt();

        $this->rate($receipt, 2, 2, 2)->assertOk();
        $this->rate($receipt, 4, 4, 4)->assertOk();

        $this->assertSame(1, SupplierRating::where('purchase_receipt_id', $receipt->id)->count());
        $this->assertSame(4, SupplierRating::where('purchase_receipt_id', $receipt->id)->value('rating'));
    }

    /** @test */
    public function scores_must_be_between_1_and_5()
    {
        $this->rate($this->receipt(), 6, 0, 3)->assertStatus(422)->assertJsonValidationErrors(['conformity', 'delay']);
        $this->assertSame(0, SupplierRating::count());
    }

    /** @test */
    public function a_receipt_without_lines_cannot_be_rated()
    {
        $empty = PurchaseReceipt::create([
            'code' => 'REC-EMPTY', 'label' => 'Vide', 'companies_id' => $this->supplier->id, 'user_id' => $this->user->id,
        ]);

        $this->rate($empty, 4, 4, 4)->assertStatus(422);
        $this->assertSame(0, SupplierRating::count());
    }

    /** @test */
    public function a_receipt_is_still_created_without_any_rating()
    {
        Status::create(['title' => 'Finished', 'order' => 1]);
        $line = $this->line();

        $response = $this->postJson(route('purchases.waiting.receipt.json.store'), [
            'code' => 'REC-NEW', 'label' => 'REC-NEW', 'companies_id' => $this->supplier->id,
            'user_id' => $this->user->id, 'line_ids' => [$line->id],
        ]);

        $response->assertOk();
        $receipt = PurchaseReceipt::where('code', 'REC-NEW')->firstOrFail();
        // La fiche propose la notation, rien ne l'impose.
        $this->assertStringContainsString('rate=1', $response->json('redirect'));
        $this->assertSame(0, SupplierRating::count());
        $this->assertNull(app(SupplierReceiptRating::class)->ratingFor($receipt));
    }

    /** @test */
    public function the_delay_score_is_suggested_from_the_promised_date()
    {
        $service = app(SupplierReceiptRating::class);
        $receivedAt = Carbon::parse('2026-10-20 15:00');

        $onTime = $this->receipt($this->line(['delivery_date' => '2026-10-25']), $receivedAt);
        $fiveDaysLate = $this->receipt($this->line(['delivery_date' => '2026-10-15']), $receivedAt);
        $noDate = $this->receipt($this->line(), $receivedAt);

        $this->assertSame(0, $service->daysLate($onTime));
        $this->assertSame(5, $service->suggestedDelayScore($onTime));
        $this->assertSame(5, $service->daysLate($fiveDaysLate));
        $this->assertSame(3, $service->suggestedDelayScore($fiveDaysLate));
        $this->assertNull($service->suggestedDelayScore($noDate));

        $this->assertSame(4, SupplierReceiptRating::delayScoreFor(2));
        $this->assertSame(2, SupplierReceiptRating::delayScoreFor(14));
        $this->assertSame(1, SupplierReceiptRating::delayScoreFor(30));
    }

    /** @test */
    public function the_rolling_average_only_counts_the_last_12_months()
    {
        $this->rate($this->receipt(), 5, 5, 5)->assertOk();
        $this->rate($this->receipt(), 3, 1, 2)->assertOk();

        $old = $this->receipt();
        $this->rate($old, 1, 1, 1)->assertOk();
        SupplierRating::where('purchase_receipt_id', $old->id)->update(['created_at' => now()->subMonths(13)]);

        $stats = app(SupplierReceiptRating::class)->rollingFor($this->supplier->id);

        $this->assertSame(2, $stats['ratings_count']);
        $this->assertEquals(4.0, $stats['quality']);
        $this->assertEquals(3.0, $stats['logistics']);
        $this->assertEquals(3.5, $stats['service']);
        $this->assertEquals(3.5, $stats['rating']);
        $this->assertEquals(70.0, $stats['composite_score']);
    }

    /** @test */
    public function a_supplier_evaluation_without_criteria_counts_its_overall_rating()
    {
        SupplierRating::create([
            'purchases_id' => $this->purchase->id, 'companies_id' => $this->supplier->id, 'rating' => 2,
        ]);

        $stats = app(SupplierReceiptRating::class)->rollingFor($this->supplier->id);

        $this->assertEquals(2.0, $stats['rating']);
        $this->assertSame(1, $stats['ratings_count']);
    }

    /** @test */
    public function a_receipt_non_conformity_weighs_on_the_quality_score()
    {
        $rated = $this->receipt();
        $this->rate($rated, 5, 5, 5)->assertOk();
        // NC déclarée après la notation : elle pèse quand même.
        $this->attachNonConformity($rated);

        $stats = app(SupplierReceiptRating::class)->rollingFor($this->supplier->id);
        $this->assertEquals(1.0, $stats['quality']);
        $this->assertEquals(5.0, $stats['logistics']);
        $this->assertSame(1, $stats['nc_receipts']);
        // La note saisie n'est pas réécrite.
        $this->assertSame(100, SupplierRating::where('purchase_receipt_id', $rated->id)->value('evaluation_score_quality'));

        // Réception non conforme jamais notée : une observation de qualité de plus.
        $this->attachNonConformity($this->receipt());
        $stats = app(SupplierReceiptRating::class)->rollingFor($this->supplier->id);
        $this->assertEquals(1.0, $stats['quality']);
        $this->assertEquals(5.0, $stats['logistics']);
        $this->assertSame(2, $stats['ratings_count']);
        $this->assertSame(2, $stats['nc_receipts']);
        $this->assertEquals(round((1 + 5 + 5) / 3, 2), $stats['rating']);
    }

    /** @test */
    public function a_canceled_non_conformity_does_not_count()
    {
        $receipt = $this->receipt();
        $this->rate($receipt, 4, 4, 4)->assertOk();
        $this->attachNonConformity($receipt, 4);

        $stats = app(SupplierReceiptRating::class)->rollingFor($this->supplier->id);
        $this->assertEquals(4.0, $stats['quality']);
        $this->assertSame(0, $stats['nc_receipts']);
    }

    /** @test */
    public function the_supplier_indicator_uses_the_rolling_score()
    {
        $this->rate($this->receipt(), 5, 3, 4)->assertOk();
        $notRated = Companies::factory()->create(['statu_supplier' => 2]);

        $indicators = app(PurchaseKPIService::class)->getSupplierCompositeIndicators();

        $this->assertSame([$this->supplier->id], $indicators->pluck('id')->all());
        $this->assertEquals(80.0, $indicators->first()->composite_score);
        $this->assertNotContains($notRated->id, $indicators->pluck('id'));
    }

    /** @test */
    public function the_receipt_page_mounts_the_rating_window()
    {
        // La fiche complète passe par du SQL MySQL (DATEDIFF) : on rend la carte seule.
        $receipt = $this->receipt();
        $state = PurchaseReceiptRatingController::state(app(SupplierReceiptRating::class), $receipt);

        $this->assertFalse($state['rated']);
        $this->assertTrue($state['can_rate']);

        $html = view('purchases.partials.receipt-supplier-rating', [
            'supplierRating'        => [
                'endpoint' => route('purchase.receipts.rating.store', ['receipt' => $receipt->id]),
                'autoOpen' => true,
                'state'    => $state,
                'texts'    => PurchaseReceiptRatingController::texts(),
            ],
        ])->render();

        $this->assertStringContainsString('id="receipt-supplier-rating-app"', $html);
        $this->assertStringContainsString('data-auto-open="1"', $html);
    }

    /** @test */
    public function receipt_ratings_do_not_replace_the_purchase_order_evaluation()
    {
        $this->rate($this->receipt(), 5, 5, 5)->assertOk();

        // La fiche commande d'achat garde son propre formulaire d'évaluation.
        $this->assertTrue($this->purchase->fresh()->Rating->isEmpty());
    }
}
