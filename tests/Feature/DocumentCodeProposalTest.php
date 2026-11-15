<?php

namespace Tests\Feature;

use App\Models\Companies\Companies;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le numéro affiché dans une fenêtre de création est seulement proposé : l'ouvrir (ou
 * l'ouvrir puis l'abandonner) ne consomme rien ; l'enregistrer avec ce numéro le consomme.
 */
class DocumentCodeProposalTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

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

    public function test_opening_the_company_form_does_not_consume_the_code(): void
    {
        $first  = $this->getJson(route('companies.json.select-data'))->assertOk()->json('next_code');
        $second = $this->getJson(route('companies.json.select-data'))->assertOk()->json('next_code');

        $this->assertSame($first, $second);
    }

    public function test_saving_with_the_proposed_code_moves_to_the_next_one(): void
    {
        $proposed = $this->getJson(route('companies.json.select-data'))->json('next_code');

        $this->postJson(route('companies.json.store'), [
            'code' => $proposed, 'label' => 'ACME', 'client_type' => 1, 'user_id' => $this->user->id,
        ])->assertCreated();

        $next = $this->getJson(route('companies.json.select-data'))->json('next_code');
        $this->assertNotSame($proposed, $next);
        $this->assertFalse(Companies::where('code', $next)->exists());
    }

    public function test_a_code_typed_by_hand_is_skipped_when_it_comes_up(): void
    {
        $proposed = $this->getJson(route('companies.json.select-data'))->json('next_code');

        // Le numéro proposé est pris à la main par une autre saisie (ou un import) : il
        // n'est plus proposé.
        Companies::factory()->create(['code' => $proposed]);

        $this->assertNotSame($proposed, $this->getJson(route('companies.json.select-data'))->json('next_code'));
    }

    public function test_opening_the_quote_form_does_not_consume_the_code(): void
    {
        $first  = $this->getJson(route('quotes.json.select-data'))->assertOk()->json('next_code');
        $second = $this->getJson(route('quotes.json.select-data'))->assertOk()->json('next_code');

        $this->assertSame($first, $second);
    }

    public function test_opening_the_purchase_form_does_not_consume_the_code(): void
    {
        $first  = $this->getJson(route('purchases.json.select-data'))->assertOk()->json('next_code');
        $second = $this->getJson(route('purchases.json.select-data'))->assertOk()->json('next_code');

        $this->assertSame($first, $second);
    }
}
