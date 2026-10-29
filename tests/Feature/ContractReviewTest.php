<?php

namespace Tests\Feature;

use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentConditions;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\User;
use App\Models\Workflow\OrderConfirmations;
use App\Models\Workflow\Orders;
use App\Services\Files\FileRole;
use App\Services\Workflow\ContractReview;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Revue de contrat d'une commande (ISO 19443) : préparation libre, visa réservé à
 * `contract-review-approve` avec auteur et date posés par le serveur, revue figée
 * une fois visée. Une revue manquante n'est qu'un indicateur.
 */
class ContractReviewTest extends TestCase
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

        config(['orders.review_checklist' => ['requirements_understood', 'lead_time_achievable']]);
    }

    private function order(array $attributes = []): Orders
    {
        return Orders::factory()->create($attributes + ['type' => 1, 'statu' => 1]);
    }

    private function grant(): void
    {
        $this->user->givePermissionTo(ContractReview::PERMISSION);
    }

    private function allChecked(): array
    {
        return ['requirements_understood' => true, 'lead_time_achievable' => true];
    }

    // ------------------------------------------------------------------
    // Visa
    // ------------------------------------------------------------------

    public function test_the_sign_off_is_refused_without_the_permission(): void
    {
        $order = $this->order();

        $this->postJson(route('orders.json.contract-review.decide', $order->id), [
            'decision' => 'approved', 'review_checklist' => $this->allChecked(),
        ])->assertForbidden()
            ->assertJsonPath('message', __('contract_review.errors.forbidden'));

        $order->refresh();
        $this->assertNull($order->review_decision);
        $this->assertNull($order->reviewed_by);
        $this->assertNull($order->reviewed_at);
    }

    public function test_preparing_the_review_does_not_need_the_permission(): void
    {
        $order = $this->order();

        $this->putJson(route('orders.json.contract-review.update', $order->id), [
            'review_gaps' => '  Délai client 3 semaines, offre à 4 : accepté par le client.  ',
            'review_checklist' => ['requirements_understood' => true, 'lead_time_achievable' => false, 'unknown' => true],
        ])->assertOk()
            ->assertJsonPath('state.frozen', false)
            ->assertJsonPath('state.status', ContractReview::STATE_TODO);

        $order->refresh();
        $this->assertSame('Délai client 3 semaines, offre à 4 : accepté par le client.', $order->review_gaps);
        // Une clé hors configuration n'est jamais enregistrée.
        $this->assertSame(['requirements_understood' => true, 'lead_time_achievable' => false], $order->review_checklist);
        $this->assertNull($order->review_decision);
    }

    public function test_reviewer_and_date_are_set_by_the_server(): void
    {
        $this->grant();
        $other = User::factory()->create();
        $order = $this->order();
        Carbon::setTestNow('2026-10-29 10:15:00');

        $this->postJson(route('orders.json.contract-review.decide', $order->id), [
            'decision' => 'approved',
            'review_checklist' => $this->allChecked(),
            'reviewed_by' => $other->id,
            'reviewed_at' => '2020-01-01 00:00:00',
        ])->assertOk()
            ->assertJsonPath('state.frozen', true)
            ->assertJsonPath('state.status', ContractReview::STATE_DONE);

        $order->refresh();
        $this->assertSame('approved', $order->review_decision);
        $this->assertSame($this->user->id, $order->reviewed_by);
        $this->assertSame('2026-10-29 10:15:00', $order->reviewed_at->format('Y-m-d H:i:s'));

        Carbon::setTestNow();
    }

    public function test_the_general_order_form_no_longer_writes_the_review(): void
    {
        $other = User::factory()->create();
        $order = $this->order();

        $this->post(route('orders.update', ['id' => $order->id]), [
            'label' => 'Commande modifiée',
            'type' => 1,
            'companies_id' => $order->companies_id,
            'companies_contacts_id' => $order->companies_contacts_id,
            'companies_addresses_id' => $order->companies_addresses_id,
            'accounting_payment_conditions_id' => AccountingPaymentConditions::factory()->create()->id,
            'accounting_payment_methods_id' => AccountingPaymentMethod::factory()->create()->id,
            'accounting_deliveries_id' => AccountingDelivery::factory()->create()->id,
            'reviewed_by' => $other->id,
            'reviewed_at' => '2026-01-01 08:00',
            'review_decision' => 'approved',
        ])->assertRedirect();

        $order->refresh();
        $this->assertSame('Commande modifiée', $order->label);
        $this->assertNull($order->review_decision);
        $this->assertNull($order->reviewed_by);
        $this->assertNull($order->reviewed_at);
        $this->assertSame(0, OrderConfirmations::where('order_id', $order->id)->count());
    }

    public function test_rejecting_requires_the_deviations(): void
    {
        $this->grant();
        $order = $this->order();

        $this->postJson(route('orders.json.contract-review.decide', $order->id), ['decision' => 'rejected'])
            ->assertStatus(422)
            ->assertJsonPath('message', __('contract_review.errors.gaps_required_reject'));
        $this->assertNull($order->fresh()->review_decision);

        $this->postJson(route('orders.json.contract-review.decide', $order->id), [
            'decision' => 'rejected', 'review_gaps' => 'Matière imposée non disponible.',
        ])->assertOk()->assertJsonPath('state.status', ContractReview::STATE_REJECTED);
    }

    public function test_approving_with_an_unchecked_item_requires_the_deviations(): void
    {
        $this->grant();
        $order = $this->order();

        $this->postJson(route('orders.json.contract-review.decide', $order->id), [
            'decision' => 'approved', 'review_checklist' => ['requirements_understood' => true],
        ])->assertStatus(422);
        $this->assertNull($order->fresh()->review_decision);

        $this->postJson(route('orders.json.contract-review.decide', $order->id), [
            'decision' => 'approved',
            'review_checklist' => ['requirements_understood' => true],
            'review_gaps' => 'Délai non tenable sur la 2e livraison, décalage accepté par écrit.',
        ])->assertOk();
        $this->assertSame('approved', $order->fresh()->review_decision);
    }

    public function test_approval_prepares_the_order_acknowledgement(): void
    {
        $this->grant();
        $order = $this->order();

        $this->postJson(route('orders.json.contract-review.decide', $order->id), [
            'decision' => 'approved', 'review_checklist' => $this->allChecked(),
        ])->assertOk();

        $this->assertSame(1, OrderConfirmations::where('order_id', $order->id)->count());
    }

    // ------------------------------------------------------------------
    // Revue figée
    // ------------------------------------------------------------------

    public function test_a_signed_off_review_is_frozen(): void
    {
        $this->grant();
        $order = $this->order();
        $this->postJson(route('orders.json.contract-review.decide', $order->id), [
            'decision' => 'approved', 'review_checklist' => $this->allChecked(), 'review_gaps' => 'Aucun',
        ])->assertOk();
        $reviewedAt = $order->fresh()->reviewed_at;

        $this->putJson(route('orders.json.contract-review.update', $order->id), ['review_gaps' => 'Réécrit après coup'])
            ->assertStatus(409)
            ->assertJsonPath('message', __('contract_review.errors.frozen'));

        $this->postJson(route('orders.json.contract-review.decide', $order->id), [
            'decision' => 'rejected', 'review_gaps' => 'Changement d\'avis',
        ])->assertStatus(409);

        $order->refresh();
        $this->assertSame('approved', $order->review_decision);
        $this->assertSame('Aucun', $order->review_gaps);
        $this->assertEquals($reviewedAt, $order->reviewed_at);
    }

    public function test_a_legacy_pending_decision_is_not_frozen(): void
    {
        $order = $this->order(['review_decision' => 'pending']);

        $this->putJson(route('orders.json.contract-review.update', $order->id), ['review_gaps' => 'À compléter'])
            ->assertOk()
            ->assertJsonPath('state.status', ContractReview::STATE_TODO)
            ->assertJsonPath('state.decision', null);
    }

    public function test_the_change_request_stays_editable_after_the_sign_off(): void
    {
        $order = $this->order([
            'review_decision' => 'approved', 'reviewed_by' => $this->user->id, 'reviewed_at' => now(),
        ]);

        $this->putJson(route('orders.json.change-request', $order->id), [
            'change_requested_by' => $this->user->id,
            'change_reason' => 'Nouvel indice B du plan client',
        ])->assertOk()->assertJsonPath('state.change.pending', true);

        $order->refresh();
        $this->assertSame('Nouvel indice B du plan client', $order->change_reason);
        $this->assertSame('approved', $order->review_decision);
    }

    public function test_an_internal_order_has_no_contract_review(): void
    {
        $this->grant();
        $order = $this->order(['type' => 2]);

        $this->postJson(route('orders.json.contract-review.decide', $order->id), [
            'decision' => 'approved', 'review_checklist' => $this->allChecked(),
        ])->assertStatus(422);

        $this->assertNull($order->fresh()->review_decision);
    }

    // ------------------------------------------------------------------
    // Affichage
    // ------------------------------------------------------------------

    public function test_the_index_reports_whether_the_review_is_done(): void
    {
        $done = $this->order(['review_decision' => 'approved']);
        $rejected = $this->order(['review_decision' => 'rejected']);
        $todo = $this->order(['review_decision' => 'pending']);
        $internal = $this->order(['type' => 2]);

        $rows = collect($this->getJson(route('orders.json.list', ['statuses' => [1]]))->assertOk()->json('data'))
            ->keyBy('id');

        $this->assertSame(ContractReview::STATE_DONE, $rows[$done->id]['contract_review']);
        $this->assertSame(ContractReview::STATE_REJECTED, $rows[$rejected->id]['contract_review']);
        $this->assertSame(ContractReview::STATE_TODO, $rows[$todo->id]['contract_review']);
        $this->assertNull($rows[$internal->id]['contract_review']);
    }

    public function test_the_order_page_shows_the_review_tab_and_a_warning(): void
    {
        $order = $this->order();

        $this->get(route('orders.show', ['id' => $order->id]))
            ->assertOk()
            ->assertSee('id="ContractReview"', false)
            ->assertSee(__('contract_review.warning_todo'))
            // L'ancien bloc du formulaire Informations a disparu.
            ->assertDontSee('name="review_decision"', false);
    }

    public function test_contract_documents_are_an_accepted_file_role(): void
    {
        $this->assertContains(FileRole::CONTRACTUAL, FileRole::all());
        $this->assertSame('contractuel', FileRole::CONTRACTUAL);
    }
}
