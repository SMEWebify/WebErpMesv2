<?php

namespace Tests\Feature;

use App\Models\Accounting\AccountingVat;
use App\Models\Products\Products;
use App\Models\User;
use App\Models\Workflow\OrderAmendment;
use App\Models\Workflow\OrderConfirmations;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Services\Production\ProductionRelease;
use App\Services\Workflow\ContractReview;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Avenants de commande : créer un avenant rouvre la revue de contrat, le visa de
 * la revue clôt l'avenant (décision, TTC après) et referme la revue. Un seul
 * avenant ouvert, annulation réservée au visa, devis d'origine jamais touché.
 */
class OrderAmendmentTest extends TestCase
{
    private User $user;
    private AccountingVat $vat;

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
        $this->vat = AccountingVat::factory()->create(['rate' => 20]);
        \App\Models\Methods\MethodsUnits::factory()->create();
        \App\Models\Methods\MethodsFamilies::factory()->create();

        config(['orders.review_checklist' => ['requirements_understood']]);
    }

    /** Commande client à la revue validée, une ligne 2 × 100 € HT à 20 % = 240 € TTC. */
    private function reviewedOrder(array $attributes = []): Orders
    {
        $order = Orders::factory()->create($attributes + [
            'type' => 1, 'statu' => 1,
            'review_decision' => 'approved', 'reviewed_by' => $this->user->id, 'reviewed_at' => '2026-10-01 09:00:00',
        ]);
        $this->addLine($order, 2, 100);

        return $order;
    }

    private function addLine(Orders $order, float $qty, float $price): OrderLines
    {
        return OrderLines::factory()->create([
            'orders_id' => $order->id, 'product_id' => Products::factory()->create()->id,
            'qty' => $qty, 'selling_price' => $price, 'discount' => 0, 'accounting_vats_id' => $this->vat->id,
            'delivered_qty' => 0, 'invoiced_qty' => 0, 'delivery_status' => 1, 'invoice_status' => 1,
            'line_type' => 'article', 'ind' => 'A',
        ]);
    }

    private function createAmendment(Orders $order, array $data = [])
    {
        return $this->postJson(route('orders.json.amendments.store', $order->id), $data + [
            'reason' => 'Passage à l\'indice B du plan client',
            'requested_by' => $this->user->id,
            'amendment_date' => '2026-10-28',
        ]);
    }

    private function approve(Orders $order, array $data = [])
    {
        return $this->postJson(route('orders.json.contract-review.decide', $order->id), $data + [
            'decision' => 'approved', 'review_checklist' => ['requirements_understood' => true],
        ]);
    }

    // ------------------------------------------------------------------
    // Avenant → revue rouverte → visa → refermée
    // ------------------------------------------------------------------

    public function test_an_amendment_reopens_the_review_until_its_sign_off(): void
    {
        $this->user->givePermissionTo(ContractReview::PERMISSION);
        $order = $this->reviewedOrder();

        // 1. Avenant : la revue figée est rouverte, la commande repasse « à faire ».
        $this->createAmendment($order)
            ->assertOk()
            ->assertJsonPath('state.status', ContractReview::STATE_TODO)
            ->assertJsonPath('state.frozen', false)
            ->assertJsonPath('state.pending_amendment.number', 1)
            ->assertJsonPath('state.change.pending', true)
            ->assertJsonPath('state.amendments.0.status', OrderAmendment::PENDING);

        $order->refresh();
        $this->assertNull($order->review_decision);
        $this->assertNull($order->reviewed_by);
        $this->assertNull($order->reviewed_at);

        $amendment = OrderAmendment::where('order_id', $order->id)->sole();
        $this->assertSame(1, $amendment->number);
        $this->assertSame('Passage à l\'indice B du plan client', $amendment->reason);
        $this->assertSame($this->user->id, $amendment->requested_by);
        $this->assertSame('2026-10-28', $amendment->amendment_date->format('Y-m-d'));
        $this->assertEqualsWithDelta(240.0, $amendment->amount_before_ttc, 0.001);
        $this->assertNull($amendment->amount_after_ttc);
        // La revue d'avant est conservée dans l'avenant.
        $this->assertSame('approved', $amendment->previous_review['decision']);
        $this->assertSame($this->user->id, $amendment->previous_review['reviewed_by']);

        // L'index des commandes signale la revue à refaire.
        $row = collect($this->getJson(route('orders.json.list', ['statuses' => [1]]))->json('data'))->firstWhere('id', $order->id);
        $this->assertSame(ContractReview::STATE_TODO, $row['contract_review']);

        // 2. La commande change (une ligne de plus : 1 × 50 € HT → +60 € TTC).
        $this->addLine($order, 1, 50);

        // 3. Visa : la revue est refermée, l'avenant clos avec la décision et le TTC après.
        Carbon::setTestNow('2026-10-30 14:00:00');
        $this->approve($order, ['review_gaps' => 'Indice B accepté, délai inchangé.'])
            ->assertOk()
            ->assertJsonPath('state.status', ContractReview::STATE_DONE)
            ->assertJsonPath('state.frozen', true)
            ->assertJsonPath('state.pending_amendment', null)
            ->assertJsonPath('state.change.pending', false)
            ->assertJsonPath('state.amendments.0.status', OrderAmendment::APPROVED);
        Carbon::setTestNow();

        $order->refresh();
        $this->assertSame('approved', $order->review_decision);
        $this->assertSame('2026-10-30 14:00:00', $order->reviewed_at->format('Y-m-d H:i:s'));

        $amendment->refresh();
        $this->assertSame(OrderAmendment::APPROVED, $amendment->status);
        $this->assertSame('approved', $amendment->review_decision);
        $this->assertSame('Indice B accepté, délai inchangé.', $amendment->review_gaps);
        $this->assertSame($this->user->id, $amendment->reviewed_by);
        $this->assertSame('2026-10-30 14:00:00', $amendment->reviewed_at->format('Y-m-d H:i:s'));
        $this->assertEqualsWithDelta(300.0, $amendment->amount_after_ttc, 0.001);

        // Le visa de l'avenant prépare un nouvel ARC, comme le premier visa.
        $this->assertSame(1, OrderConfirmations::where('order_id', $order->id)->count());
    }

    public function test_rejecting_the_review_rejects_the_amendment(): void
    {
        $this->user->givePermissionTo(ContractReview::PERMISSION);
        $order = $this->reviewedOrder();
        $this->createAmendment($order)->assertOk();

        $this->postJson(route('orders.json.contract-review.decide', $order->id), [
            'decision' => 'rejected', 'review_gaps' => 'Délai de l\'indice B intenable.',
        ])->assertOk()->assertJsonPath('state.status', ContractReview::STATE_REJECTED);

        $amendment = OrderAmendment::where('order_id', $order->id)->sole();
        $this->assertSame(OrderAmendment::REJECTED, $amendment->status);
        $this->assertSame('rejected', $amendment->review_decision);
    }

    public function test_successive_amendments_are_numbered_and_kept(): void
    {
        $this->user->givePermissionTo(ContractReview::PERMISSION);
        $order = $this->reviewedOrder();

        $this->createAmendment($order, ['reason' => 'Quantité portée à 3'])->assertOk();
        $this->approve($order)->assertOk();
        $this->createAmendment($order, ['reason' => 'Délai avancé d\'une semaine'])
            ->assertOk()
            ->assertJsonPath('state.pending_amendment.number', 2)
            // Historique du plus récent au plus ancien.
            ->assertJsonPath('state.amendments.0.number', 2)
            ->assertJsonPath('state.amendments.1.number', 1)
            ->assertJsonPath('state.amendments.1.status', OrderAmendment::APPROVED);

        $this->assertSame(2, OrderAmendment::where('order_id', $order->id)->count());
    }

    // ------------------------------------------------------------------
    // Règles
    // ------------------------------------------------------------------

    public function test_only_one_amendment_can_be_open(): void
    {
        $order = $this->reviewedOrder();
        $this->createAmendment($order)->assertOk();

        $this->createAmendment($order, ['reason' => 'Autre changement'])
            ->assertStatus(409)
            ->assertJsonPath('message', __('contract_review.amendment.errors.already_pending'));

        $this->assertSame(1, OrderAmendment::where('order_id', $order->id)->count());
    }

    public function test_creating_an_amendment_does_not_need_the_permission_but_requires_a_reason(): void
    {
        $order = $this->reviewedOrder();

        $this->createAmendment($order, ['reason' => ''])->assertStatus(422)->assertJsonValidationErrors('reason');
        $this->assertSame('approved', $order->fresh()->review_decision);

        $this->createAmendment($order)->assertOk();
        $this->assertNull($order->fresh()->review_decision);
    }

    public function test_an_internal_order_has_no_amendment(): void
    {
        $order = $this->reviewedOrder(['type' => 2, 'review_decision' => null, 'reviewed_by' => null, 'reviewed_at' => null]);

        $this->createAmendment($order)
            ->assertStatus(422)
            ->assertJsonPath('message', __('contract_review.amendment.errors.not_applicable'));

        $this->assertSame(0, OrderAmendment::count());
    }

    public function test_cancelling_restores_the_previous_review_and_needs_the_permission(): void
    {
        $order = $this->reviewedOrder();
        $this->createAmendment($order)->assertOk();
        $amendment = OrderAmendment::where('order_id', $order->id)->sole();
        $url = route('orders.json.amendments.cancel', ['id' => $order->id, 'amendment' => $amendment->id]);

        $this->postJson($url)->assertForbidden();
        $this->assertTrue($amendment->fresh()->isPending());

        $this->user->givePermissionTo(ContractReview::PERMISSION);
        $this->postJson($url)
            ->assertOk()
            ->assertJsonPath('state.status', ContractReview::STATE_DONE)
            ->assertJsonPath('state.frozen', true)
            ->assertJsonPath('state.pending_amendment', null)
            ->assertJsonPath('state.amendments.0.status', OrderAmendment::CANCELLED);

        $order->refresh();
        $this->assertSame('approved', $order->review_decision);
        $this->assertSame($this->user->id, $order->reviewed_by);
        $this->assertSame('2026-10-01 09:00:00', $order->reviewed_at->format('Y-m-d H:i:s'));

        // Une fois clos, l'avenant ne s'annule plus.
        $this->postJson($url)->assertStatus(409);
    }

    public function test_an_amendment_of_another_order_cannot_be_cancelled_through_this_one(): void
    {
        $this->user->givePermissionTo(ContractReview::PERMISSION);
        $order = $this->reviewedOrder();
        $other = $this->reviewedOrder();
        $this->createAmendment($other)->assertOk();
        $amendment = OrderAmendment::where('order_id', $other->id)->sole();

        $this->postJson(route('orders.json.amendments.cancel', ['id' => $order->id, 'amendment' => $amendment->id]))
            ->assertNotFound();
        $this->assertTrue($amendment->fresh()->isPending());
    }

    public function test_a_pending_amendment_prevents_the_production_release(): void
    {
        $order = $this->reviewedOrder();
        $line = $order->OrderLines()->first();
        $release = app(ProductionRelease::class);

        $this->assertNull($release->releaseRefusal($line));

        $this->createAmendment($order)->assertOk();
        $this->assertSame(__('production_release.amendment_pending'), $release->releaseRefusal($line->fresh()));
    }

    public function test_the_amendment_does_not_touch_the_original_quote(): void
    {
        $this->user->givePermissionTo(ContractReview::PERMISSION);
        $quote = \App\Models\Workflow\Quotes::factory()->create(['statu' => 3]);
        $order = $this->reviewedOrder(['quotes_id' => $quote->id]);
        $before = $quote->fresh()->getAttributes();

        $this->createAmendment($order)->assertOk();
        $this->addLine($order, 5, 10);
        $this->approve($order)->assertOk();

        $after = $quote->fresh()->getAttributes();
        unset($before['updated_at'], $after['updated_at']);
        $this->assertSame($before, $after);
    }

    // ------------------------------------------------------------------
    // Ancienne demande de changement
    // ------------------------------------------------------------------

    public function test_the_legacy_change_request_is_shown_read_only(): void
    {
        $order = $this->reviewedOrder([
            'change_requested_by' => $this->user->id, 'change_reason' => 'Nouvel indice B du plan client',
        ]);

        $this->getJson(route('orders.json.contract-review', $order->id))
            ->assertOk()
            ->assertJsonPath('state.legacy_change.reason', 'Nouvel indice B du plan client')
            ->assertJsonPath('state.legacy_change.requested_by', $this->user->name)
            ->assertJsonPath('state.legacy_change.pending', true)
            ->assertJsonPath('state.change.pending', true);

        // Plus aucune route n'écrit ces colonnes.
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('orders.json.change-request'));
    }

    public function test_the_order_page_warns_about_the_pending_amendment(): void
    {
        $order = $this->reviewedOrder();
        $this->createAmendment($order)->assertOk();

        $this->get(route('orders.show', ['id' => $order->id]))
            ->assertOk()
            ->assertSee(__('contract_review.amendment.warning', ['number' => 1]));
    }
}
