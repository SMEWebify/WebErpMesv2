<?php

namespace Tests\Feature;

use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentConditions;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\User;
use App\Models\Workflow\Quotes;
use Tests\TestCase;

/**
 * Onglet « Revue » du devis : revue et demande de changement enregistrées à part
 * du formulaire Informations, mêmes champs et mêmes règles qu'avant.
 */
class QuoteReviewTabTest extends TestCase
{
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

    public function test_the_review_tab_saves_review_and_change_request(): void
    {
        $quote = Quotes::factory()->create(['statu' => 1, 'user_id' => $this->user->id]);

        $this->post(route('quotes.review.update', ['id' => $quote->id]), [
            'reviewed_by' => $this->user->id,
            'reviewed_at' => '2026-10-29 09:30',
            'review_decision' => 'approved',
            'change_requested_by' => $this->user->id,
            'change_reason' => 'Quantité portée à 12',
        ])->assertRedirect(route('quotes.show', ['id' => $quote->id]) . '#Review');

        $quote->refresh();
        $this->assertSame('approved', $quote->review_decision);
        $this->assertSame($this->user->id, $quote->reviewed_by);
        $this->assertSame('Quantité portée à 12', $quote->change_reason);
    }

    public function test_the_information_form_no_longer_writes_the_review(): void
    {
        $quote = Quotes::factory()->create(['statu' => 1, 'user_id' => $this->user->id, 'review_decision' => 'pending']);

        $this->post(route('quotes.update', ['id' => $quote->id]), [
            'label' => 'Devis modifié',
            'companies_id' => $quote->companies_id,
            'companies_contacts_id' => $quote->companies_contacts_id,
            'companies_addresses_id' => $quote->companies_addresses_id,
            'accounting_payment_conditions_id' => AccountingPaymentConditions::factory()->create()->id,
            'accounting_payment_methods_id' => AccountingPaymentMethod::factory()->create()->id,
            'accounting_deliveries_id' => AccountingDelivery::factory()->create()->id,
            'review_decision' => 'rejected',
        ])->assertRedirect();

        $quote->refresh();
        $this->assertSame('Devis modifié', $quote->label);
        // Le formulaire Informations n'envoie plus ces champs : la revue reste intacte.
        $this->assertSame('pending', $quote->review_decision);
    }

    public function test_the_quote_page_has_the_review_tab(): void
    {
        $quote = Quotes::factory()->create(['statu' => 1, 'user_id' => $this->user->id]);

        $this->get(route('quotes.show', ['id' => $quote->id]))
            ->assertOk()
            ->assertSee('id="Review"', false)
            ->assertSee(route('quotes.review.update', ['id' => $quote->id]), false);
    }
}
