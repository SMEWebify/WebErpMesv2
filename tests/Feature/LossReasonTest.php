<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Str;
use App\Models\Workflow\Quotes;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\LossReason;
use App\Models\Workflow\Opportunities;
use App\Models\Workflow\AffairCategory;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Motif de perte : exigé quand un devis passe en Perdu (4) ou une opportunité en
 * Perdue (5) ; les documents déjà perdus avant sa mise en place restent valides.
 */
class LossReasonTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

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
    }

    private function reason(string $code = 'PRICE'): LossReason
    {
        return LossReason::where('code', $code)->sole();
    }

    private function opportunity(array $attributes = []): Opportunities
    {
        $client = Quotes::factory()->make();

        return Opportunities::create($attributes + [
            'uuid'                   => (string) Str::uuid(),
            'companies_id'           => $client->companies_id,
            'companies_contacts_id'  => $client->companies_contacts_id,
            'companies_addresses_id' => $client->companies_addresses_id,
            'user_id'                => $this->user->id,
            'label'                  => 'Affaire test',
            'budget'                 => 1000,
            'statu'                  => 3,
            'probality'              => 50,
        ]);
    }

    public function test_the_migration_provides_the_usual_reasons(): void
    {
        $this->assertEqualsCanonicalizing(
            ['PRICE', 'DELAY', 'TECHNICAL', 'COMPETITOR', 'NO_FOLLOW_UP'],
            LossReason::active()->pluck('code')->all()
        );
    }

    public function test_a_quote_cannot_become_lost_without_a_reason(): void
    {
        $quote = Quotes::factory()->create(['statu' => 2]);

        $this->postJson(route('quotes.json.statu', $quote->id), ['statu' => 4])
            ->assertStatus(422)
            ->assertJsonValidationErrors('loss_reason_id')
            ->assertJsonPath('loss_reason.status', 4)
            ->assertJsonPath('loss_reason.required', true)
            ->assertJsonCount(5, 'loss_reason.reasons');

        $this->assertSame(2, (int) $quote->fresh()->statu);
    }

    public function test_a_quote_becomes_lost_with_its_reason_and_passes_it_to_its_opportunity(): void
    {
        $opportunity = $this->opportunity();
        $quote = Quotes::factory()->create(['statu' => 2, 'opportunities_id' => $opportunity->id]);
        $line  = QuoteLines::factory()->create(['quotes_id' => $quote->id, 'statu' => 2]);

        $this->postJson(route('quotes.json.statu', $quote->id), [
            'statu'          => 4,
            'loss_reason_id' => $this->reason('COMPETITOR')->id,
            'loss_comment'   => '  Concurrent moins cher de 12 %  ',
        ])->assertOk();

        $quote->refresh();
        $this->assertSame(4, (int) $quote->statu);
        $this->assertSame($this->reason('COMPETITOR')->id, (int) $quote->loss_reason_id);
        $this->assertSame('Concurrent moins cher de 12 %', $quote->loss_comment);
        $this->assertSame(4, (int) $line->fresh()->statu);

        $opportunity->refresh();
        $this->assertSame(5, (int) $opportunity->statu);
        $this->assertSame($this->reason('COMPETITOR')->id, (int) $opportunity->loss_reason_id);
        $this->assertSame('Concurrent moins cher de 12 %', $opportunity->loss_comment);
    }

    public function test_an_inactive_reason_is_refused(): void
    {
        $reason = $this->reason('DELAY');
        $reason->update(['active' => false]);
        $quote = Quotes::factory()->create(['statu' => 1]);

        $this->postJson(route('quotes.json.statu', $quote->id), ['statu' => 4, 'loss_reason_id' => $reason->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('loss_reason_id');
    }

    public function test_without_any_active_reason_the_reason_is_not_required(): void
    {
        LossReason::query()->update(['active' => false]);
        $quote = Quotes::factory()->create(['statu' => 2]);

        $this->postJson(route('quotes.json.statu', $quote->id), ['statu' => 4])->assertOk();

        $this->assertSame(4, (int) $quote->fresh()->statu);
        $this->assertNull($quote->fresh()->loss_reason_id);
    }

    public function test_quotes_already_lost_stay_valid_without_a_reason(): void
    {
        $quote = Quotes::factory()->create(['statu' => 4]);
        $this->assertNull($quote->loss_reason_id);

        // Re-poser le même statut ou en changer n'exige rien.
        $this->postJson(route('quotes.json.statu', $quote->id), ['statu' => 4])->assertOk();
        $this->postJson(route('quotes.json.statu', $quote->id), ['statu' => 6])->assertOk();
        $this->assertSame(6, (int) $quote->fresh()->statu);

        // Il apparaît dans la statistique, comme « non renseigné ».
        $legacy = Quotes::factory()->create(['statu' => 4]);
        $this->getJson(route('quotes.json.loss-stats', ['period' => 'all']))
            ->assertOk()
            ->assertJsonPath('total.count', 1)
            ->assertJsonPath('reasons.0.key', 'none')
            ->assertJsonPath('reasons.0.count', 1);

        // Le renseigner après coup est possible, sans changer le statut.
        $this->postJson(route('quotes.json.loss-reason', $legacy->id), ['loss_reason_id' => $this->reason('PRICE')->id])
            ->assertOk();
        $this->assertSame($this->reason('PRICE')->id, (int) $legacy->fresh()->loss_reason_id);
        $this->assertSame(4, (int) $legacy->fresh()->statu);
    }

    public function test_the_reason_cannot_be_set_on_a_quote_that_is_not_lost(): void
    {
        $quote = Quotes::factory()->create(['statu' => 2]);

        $this->postJson(route('quotes.json.loss-reason', $quote->id), ['loss_reason_id' => $this->reason()->id])
            ->assertStatus(422);
        $this->assertNull($quote->fresh()->loss_reason_id);
    }

    public function test_an_opportunity_cannot_become_lost_without_a_reason(): void
    {
        $opportunity = $this->opportunity();

        $this->postJson(route('opportunities.json.statu', $opportunity->id), ['statu' => 5])
            ->assertStatus(422)
            ->assertJsonPath('loss_reason.kind', 'opportunity');
        $this->putJson(route('opportunities.json.kanban-move', $opportunity->id), ['statu' => 5])
            ->assertStatus(422)
            ->assertJsonValidationErrors('loss_reason_id');
        $this->assertSame(3, (int) $opportunity->fresh()->statu);

        $this->putJson(route('opportunities.json.kanban-move', $opportunity->id), [
            'statu' => 5, 'loss_reason_id' => $this->reason('NO_FOLLOW_UP')->id,
        ])->assertOk();

        $opportunity->refresh();
        $this->assertSame(5, (int) $opportunity->statu);
        $this->assertSame($this->reason('NO_FOLLOW_UP')->id, (int) $opportunity->loss_reason_id);

        // Les autres statuts ne demandent rien.
        $other = $this->opportunity();
        $this->postJson(route('opportunities.json.statu', $other->id), ['statu' => 4])->assertOk();
    }

    public function test_opportunities_already_lost_stay_valid_without_a_reason(): void
    {
        $opportunity = $this->opportunity(['statu' => 5]);

        $this->putJson(route('opportunities.json.kanban-move', $opportunity->id), ['statu' => 6])->assertOk();
        $this->assertSame(6, (int) $opportunity->fresh()->statu);
    }

    public function test_loss_statistics_by_reason_and_category(): void
    {
        $category = AffairCategory::create(['code' => 'DEBIT', 'label' => 'Débitmétrie', 'ordre' => 10, 'active' => true]);
        $price = $this->reason('PRICE');
        $delay = $this->reason('DELAY');

        $lost = function (?LossReason $reason, ?AffairCategory $category, float $amount, array $extra = []) {
            $quote = Quotes::factory()->create($extra + [
                'statu'              => 4,
                'loss_reason_id'     => $reason?->id,
                'affair_category_id' => $category?->id,
            ]);
            QuoteLines::factory()->create(['quotes_id' => $quote->id, 'qty' => 2, 'selling_price' => $amount / 2, 'discount' => 0]);

            return $quote;
        };

        $lost($price, $category, 1000);
        $lost($price, null, 500);
        $lost($delay, $category, 200);
        $lost(null, null, 100);
        // Ni les trames, ni les devis non perdus, ni hors période.
        $lost($price, $category, 9999, ['is_template' => true]);
        Quotes::factory()->create(['statu' => 3, 'loss_reason_id' => $price->id]);
        $old = $lost($delay, null, 300);
        Quotes::withTemplates()->whereKey($old->id)->update(['created_at' => now()->subYears(2)]);

        $data = $this->getJson(route('quotes.json.loss-stats', ['period' => '12m']))->assertOk()->json();

        $this->assertSame(4, $data['total']['count']);
        $this->assertEqualsWithDelta(1800, $data['total']['amount'], 0.01);

        $byReason = collect($data['reasons'])->keyBy('key');
        $this->assertSame(['' . $price->id, '' . $delay->id, 'none'], array_column($data['reasons'], 'key'));
        $this->assertSame(2, $byReason[(string) $price->id]['count']);
        $this->assertEqualsWithDelta(1500, $byReason[(string) $price->id]['amount'], 0.01);
        $this->assertEquals(50.0, $byReason[(string) $price->id]['share']);

        $byCategory = collect($data['categories'])->keyBy('key');
        $this->assertSame(2, $byCategory[(string) $category->id]['count']);
        $this->assertSame([(string) $price->id => 1, (string) $delay->id => 1], $byCategory[(string) $category->id]['by_reason']);
        $this->assertSame(2, $byCategory['none']['count']);

        $this->getJson(route('quotes.json.loss-stats', ['period' => 'all']))
            ->assertJsonPath('total.count', 5);
    }

    public function test_settings_screen_requires_the_methods_permission(): void
    {
        $this->get(route('methods.loss-reason'))->assertForbidden();

        Permission::firstOrCreate(['name' => 'methods-menu', 'guard_name' => 'web']);
        $this->user->givePermissionTo('methods-menu');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->post(route('methods.loss-reason.create'), ['code' => 'QUALITY', 'label' => 'Qualité', 'ordre' => 60])
            ->assertRedirect(route('methods.loss-reason'));
        $reason = LossReason::where('code', 'QUALITY')->sole();

        $this->post(route('methods.loss-reason.update', ['id' => $reason->id]), ['label' => 'Qualité perçue', 'ordre' => 60, 'active' => 0])
            ->assertRedirect(route('methods.loss-reason'));
        $this->assertFalse($reason->fresh()->active);

        $this->get(route('methods.loss-reason'))->assertOk()->assertSee('Qualité perçue');
    }
}
