<?php

namespace Tests\Feature\Affairs;

use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompaniesContacts;
use App\Models\User;
use App\Models\Workflow\AffairCategory;
use App\Models\Workflow\AffairRisk;
use App\Models\Workflow\Opportunities;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use App\Services\Affairs\AffairRiskRegister;
use App\Services\Affairs\AffairSummaryService;
use App\Services\Workflow\ContractReview;
use Carbon\Carbon;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Registre des risques d'une affaire : criticité, blocages de la synthèse,
 * clôture d'un risque critique réservée à `affair-risk-close`, avertissement
 * (jamais bloquant) de la revue de contrat sur une catégorie qui l'exige.
 *
 * Date de référence figée au mercredi 7 octobre 2026, 12h.
 */
class AffairRiskRegisterTest extends TestCase
{
    private User $user;
    private Companies $company;
    private Opportunities $opportunity;
    private Carbon $now;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);

        $this->now = Carbon::parse('2026-10-07 12:00:00');
        Carbon::setTestNow($this->now);
        config(['affairs.risks.critical_threshold' => 9]);

        // Seeder, pas migration : créée ici comme en production.
        Permission::firstOrCreate(['name' => 'opportunities-menu', 'guard_name' => 'web']);

        $this->user = User::factory()->create();
        $this->user->givePermissionTo('opportunities-menu');
        $this->actingAs($this->user);

        $this->company = Companies::factory()->create(['statu_customer' => 2]);
        $this->opportunity = Opportunities::factory()->create([
            'companies_id'           => $this->company->id,
            'companies_contacts_id'  => CompaniesContacts::factory()->create(['companies_id' => $this->company->id])->id,
            'companies_addresses_id' => CompaniesAddresses::factory()->create(['companies_id' => $this->company->id])->id,
            'user_id'                => $this->user->id,
            'leads_id'               => null,
            'statu'                  => 1,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function risk(array $attributes = []): AffairRisk
    {
        return AffairRisk::forceCreate($attributes + [
            'opportunities_id' => $this->opportunity->id,
            'label'            => 'Soudure en position',
            'category'         => 'technical',
            'probability'      => 2,
            'severity'         => 2,
            'status'           => 'open',
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'label' => 'Délai matière', 'category' => 'supply', 'probability' => 2, 'severity' => 2,
            'status' => 'open', 'mitigation' => 'Commande anticipée', 'owner_id' => $this->user->id,
            'due_date' => '2026-10-20',
        ];
    }

    private function summary(): array
    {
        return (new AffairSummaryService())->summarize($this->opportunity->fresh(), null, $this->now);
    }

    private function order(?AffairCategory $category = null): Orders
    {
        $quote = Quotes::factory()->create([
            'opportunities_id' => $this->opportunity->id, 'companies_id' => $this->company->id, 'statu' => 3,
        ]);

        return Orders::factory()->create([
            'quotes_id' => $quote->id, 'companies_id' => $this->company->id, 'type' => 1, 'statu' => 1,
            'affair_category_id' => $category?->id,
        ]);
    }

    // ------------------------------------------------------------------
    // Criticité
    // ------------------------------------------------------------------

    public function test_criticality_is_probability_times_severity_and_critical_from_the_threshold(): void
    {
        $this->assertSame(12, AffairRiskRegister::criticality(3, 4));
        $this->assertSame(12, $this->risk(['probability' => 3, 'severity' => 4])->criticality);
        $this->assertTrue(AffairRiskRegister::isCritical(3, 3));
        $this->assertFalse(AffairRiskRegister::isCritical(2, 4));

        config(['affairs.risks.critical_threshold' => 8]);
        $this->assertTrue(AffairRiskRegister::isCritical(2, 4));
    }

    public function test_the_register_lists_criticality_and_overdue_flags(): void
    {
        $this->risk(['probability' => 3, 'severity' => 4, 'due_date' => '2026-10-01']);

        $this->getJson(route('opportunities.json.risks', $this->opportunity->id))
            ->assertOk()
            ->assertJsonPath('data.0.criticality', 12)
            ->assertJsonPath('data.0.critical', true)
            ->assertJsonPath('data.0.overdue', true)
            ->assertJsonPath('meta.threshold', 9)
            ->assertJsonPath('meta.can_close', false);
    }

    // ------------------------------------------------------------------
    // Blocages de la synthèse
    // ------------------------------------------------------------------

    public function test_an_open_critical_risk_is_flagged(): void
    {
        $this->risk(['label' => 'Contrôle radio', 'probability' => 3, 'severity' => 3]);
        $this->risk(['label' => 'Mineur', 'probability' => 2, 'severity' => 4]); // 8 < 9

        $summary = $this->summary();

        $this->assertSame(['risk_critical'], array_column($summary['blockers'], 'code'));
        $this->assertSame('danger', $summary['blockers'][0]['severity']);
        $this->assertSame(9, $summary['blockers'][0]['params']['criticality']);
        $this->assertSame('treat_risk', $summary['next_action']['code']);
        $this->assertStringEndsWith('#Risks', $summary['blockers'][0]['url']);
        $this->assertCount(1, $summary['risks']['critical']);
        $this->assertSame('Contrôle radio', $summary['risks']['critical'][0]['label']);
        $this->assertSame(2, $summary['risks']['active']);
    }

    public function test_an_active_risk_past_its_due_date_is_flagged(): void
    {
        $this->risk(['label' => 'Plan client', 'due_date' => '2026-10-01']);
        $this->risk(['label' => 'Aujourd\'hui', 'due_date' => '2026-10-07']); // pas encore échu
        $this->risk(['label' => 'Survenu', 'status' => 'occurred', 'due_date' => '2026-10-05']);

        $summary = $this->summary();

        $this->assertSame(['risk_overdue', 'risk_overdue'], array_column($summary['blockers'], 'code'));
        $this->assertSame('warning', $summary['blockers'][0]['severity']);
        $this->assertSame(6, $summary['blockers'][0]['params']['days']);
        $this->assertSame(2, $summary['risks']['overdue']);
    }

    public function test_mitigated_or_closed_risks_are_not_flagged(): void
    {
        $this->risk(['probability' => 4, 'severity' => 4, 'status' => 'mitigated', 'due_date' => '2026-10-01']);
        $this->risk(['probability' => 4, 'severity' => 4, 'status' => 'closed', 'due_date' => '2026-10-01']);

        $summary = $this->summary();

        $this->assertSame([], $summary['blockers']);
        $this->assertSame([], $summary['risks']['critical']);
        $this->assertSame(0, $summary['risks']['active']);
    }

    // ------------------------------------------------------------------
    // Saisie et clôture
    // ------------------------------------------------------------------

    public function test_a_risk_is_recorded_and_logged(): void
    {
        $this->postJson(route('opportunities.json.risks.store', $this->opportunity->id), $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.0.label', 'Délai matière')
            ->assertJsonPath('data.0.owner_name', $this->user->name);

        $risk = AffairRisk::firstOrFail();
        $this->assertSame($this->user->id, $risk->created_by);
        $this->assertTrue(Activity::where('subject_type', AffairRisk::class)->where('subject_id', $risk->id)->exists());
    }

    public function test_recording_a_risk_requires_the_opportunities_module(): void
    {
        $this->user->revokePermissionTo('opportunities-menu');

        $this->postJson(route('opportunities.json.risks.store', $this->opportunity->id), $this->payload())
            ->assertForbidden();
        $this->assertSame(0, AffairRisk::count());
    }

    public function test_closing_a_critical_risk_requires_the_permission(): void
    {
        $risk = $this->risk(['probability' => 3, 'severity' => 4]);
        $url = route('opportunities.json.risks.update', ['id' => $this->opportunity->id, 'risk' => $risk->id]);

        $this->putJson($url, $this->payload(['probability' => 3, 'severity' => 4, 'status' => 'closed']))
            ->assertForbidden()
            ->assertJsonPath('message', __('affairs.risks.errors.close_forbidden'));
        // Baisser la gravité dans la même saisie ne dispense pas
        $this->putJson($url, $this->payload(['probability' => 1, 'severity' => 1, 'status' => 'closed']))
            ->assertForbidden();
        $this->assertSame('open', $risk->fresh()->status);

        // Autres statuts libres
        $this->putJson($url, $this->payload(['probability' => 3, 'severity' => 4, 'status' => 'mitigated']))->assertOk();

        $this->user->givePermissionTo(AffairRiskRegister::PERMISSION);
        $this->putJson($url, $this->payload(['probability' => 3, 'severity' => 4, 'status' => 'closed']))->assertOk();

        $risk->refresh();
        $this->assertSame('closed', $risk->status);
        $this->assertSame($this->user->id, $risk->closed_by);
        $this->assertNotNull($risk->closed_at);
    }

    public function test_a_new_critical_risk_cannot_be_created_already_closed_without_the_permission(): void
    {
        $this->postJson(route('opportunities.json.risks.store', $this->opportunity->id),
            $this->payload(['probability' => 4, 'severity' => 4, 'status' => 'closed']))
            ->assertForbidden();
        $this->assertSame(0, AffairRisk::count());
    }

    public function test_closing_a_minor_risk_is_free(): void
    {
        $risk = $this->risk();

        $this->putJson(route('opportunities.json.risks.update', ['id' => $this->opportunity->id, 'risk' => $risk->id]),
            $this->payload(['status' => 'closed']))->assertOk();

        $this->assertSame('closed', $risk->fresh()->status);
    }

    public function test_the_linked_order_must_belong_to_the_affair(): void
    {
        $foreign = Orders::factory()->create(['companies_id' => $this->company->id, 'type' => 1]);

        $this->postJson(route('opportunities.json.risks.store', $this->opportunity->id),
            $this->payload(['orders_id' => $foreign->id]))
            ->assertStatus(422);

        $own = $this->order();
        $this->postJson(route('opportunities.json.risks.store', $this->opportunity->id),
            $this->payload(['orders_id' => $own->id]))
            ->assertCreated()
            ->assertJsonPath('data.0.order_code', $own->code);
    }

    public function test_a_risk_of_another_affair_cannot_be_edited_through_this_one(): void
    {
        $other = Opportunities::factory()->create([
            'companies_id' => $this->company->id, 'companies_contacts_id' => $this->opportunity->companies_contacts_id,
            'companies_addresses_id' => $this->opportunity->companies_addresses_id, 'user_id' => $this->user->id,
            'leads_id' => null, 'statu' => 1,
        ]);
        $risk = $this->risk(['opportunities_id' => $other->id]);

        $this->putJson(route('opportunities.json.risks.update', ['id' => $this->opportunity->id, 'risk' => $risk->id]),
            $this->payload())->assertNotFound();
    }

    // ------------------------------------------------------------------
    // Revue de contrat
    // ------------------------------------------------------------------

    public function test_inactive_option_gives_no_contract_review_warning(): void
    {
        $order = $this->order(AffairCategory::create(['code' => 'STD', 'label' => 'Standard']));
        $this->assertNull(app(ContractReview::class)->state($order, $this->user)['risk_register']);

        $this->assertNull(app(ContractReview::class)->state($this->order(), $this->user)['risk_register']);
    }

    public function test_required_register_warns_until_a_risk_is_recorded_without_blocking_the_sign_off(): void
    {
        $category = AffairCategory::create(['code' => 'NUC', 'label' => 'Nucléaire', 'requires_risk_register' => true]);
        $order = $this->order($category);

        $warning = app(ContractReview::class)->state($order, $this->user)['risk_register'];
        $this->assertNotNull($warning);
        $this->assertStringEndsWith('#Risks', $warning['opportunity_url']);

        // Jamais bloquant : le visa passe malgré l'avertissement
        $this->user->givePermissionTo(ContractReview::PERMISSION);
        config(['orders.review_checklist' => []]);
        $this->postJson(route('orders.json.contract-review.decide', $order->id), ['decision' => 'approved'])
            ->assertOk();
        $this->assertSame('approved', $order->fresh()->review_decision);

        $this->risk();
        $this->assertNull(app(ContractReview::class)->state($order->fresh(), $this->user)['risk_register']);
    }
}
