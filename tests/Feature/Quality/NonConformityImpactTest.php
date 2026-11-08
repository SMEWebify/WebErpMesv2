<?php

namespace Tests\Feature\Quality;

use App\Models\Companies\Companies;
use App\Models\Methods\MethodsServices;
use App\Models\Planning\Status;
use App\Models\Planning\Task;
use App\Models\Planning\TaskActivities;
use App\Models\Products\Products;
use App\Models\Quality\QualityNonConformity;
use App\Models\User;
use App\Models\Workflow\OrderLineDelayHistory;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Services\Production\ProductionRelease;
use App\Services\Production\TaskRelease;
use App\Services\Quality\NonConformityImpact;
use App\Services\Quality\NonConformityImpactException;
use Carbon\Carbon;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * Impact d'une NC : coûts saisis et réels, ordre de retouche (tâches de la ligne de
 * commande, non facturables), report de délai proposé puis historisé, indicateur
 * coût de non-qualité.
 */
class NonConformityImpactTest extends TestCase
{
    private User $user;
    private Companies $customer;
    private MethodsServices $welding;
    private array $statuses = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
        ]);

        Carbon::setTestNow('2026-11-08 10:00:00');

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        \App\Models\Methods\MethodsUnits::factory()->create();
        \App\Models\Methods\MethodsFamilies::factory()->create();
        foreach (['Open', 'Started', 'In progress', 'Finished'] as $order => $title) {
            $this->statuses[$title] = Status::create(['title' => $title, 'order' => $order])->id;
        }

        $this->customer = Companies::factory()->create(['statu_customer' => 2, 'statu_supplier' => 1]);
        $this->welding = MethodsServices::factory()->create([
            'code' => 'SOUD', 'label' => 'Soudure', 'type' => MethodsServices::TYPE_PRODUCTIVE, 'hourly_rate' => 60,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // Fabriques
    // ------------------------------------------------------------------

    private function line(array $attributes = []): OrderLines
    {
        $order = Orders::factory()->create(['companies_id' => $this->customer->id, 'type' => 1, 'statu' => 1]);

        return OrderLines::factory()->create($attributes + [
            'orders_id' => $order->id, 'product_id' => Products::factory()->create()->id, 'qty' => 4,
            'selling_price' => 250, 'discount' => 0,
            'delivered_qty' => 0, 'invoiced_qty' => 0, 'delivery_status' => 1, 'invoice_status' => 1,
            'line_type' => 'article', 'ind' => 'A', 'delivery_date' => '2026-11-30',
        ]);
    }

    private function nc(?OrderLines $line, array $attributes = []): QualityNonConformity
    {
        return QualityNonConformity::create($attributes + [
            'code' => 'NC-0007', 'label' => 'Cordon de soudure poreux', 'statu' => 1, 'type' => 1,
            'user_id' => $this->user->id, 'order_lines_id' => $line?->id, 'qty' => 2,
        ]);
    }

    private function grant(): void
    {
        $this->user->givePermissionTo(NonConformityImpact::PERMISSION);
    }

    private function service(): NonConformityImpact
    {
        return app(NonConformityImpact::class);
    }

    private function log(Task $task, string $from, string $to): void
    {
        TaskActivities::create(['task_id' => $task->id, 'type' => TaskActivities::TYPE_START, 'timestamp' => $from, 'user_id' => $this->user->id]);
        TaskActivities::create(['task_id' => $task->id, 'type' => TaskActivities::TYPE_END, 'timestamp' => $to, 'user_id' => $this->user->id]);
    }

    // ------------------------------------------------------------------
    // Ordre de retouche
    // ------------------------------------------------------------------

    public function test_a_rework_order_creates_linked_tasks_on_the_order_line(): void
    {
        $this->grant();
        $line = $this->line();
        $nc = $this->nc($line);

        $this->postJson(route('quality.nonConformitie.api.rework', $nc->id), ['operations' => [
            ['methods_services_id' => $this->welding->id, 'hours' => 1.5, 'label' => ''],
        ]])->assertCreated()
            ->assertJsonPath('impact.rework.0.service', 'Soudure')
            ->assertJsonPath('impact.rework_in_progress', true);

        $task = Task::where('quality_non_conformity_id', $nc->id)->sole();
        $this->assertSame($line->id, (int) $task->order_lines_id);
        $this->assertSame(NonConformityImpact::TASK_ORIGIN, $task->origin);
        $this->assertSame($this->welding->id, (int) $task->methods_services_id);
        $this->assertSame($this->statuses['Open'], (int) $task->status_id);
        $this->assertEquals(1.5, (float) $task->seting_time);
        $this->assertEquals(2, (float) $task->planned_qty);
        $this->assertEquals(0, (float) $task->unit_price);
        $this->assertEquals(0, (float) $task->unit_cost);
        $this->assertSame(__('nc_impact.rework_label', ['code' => 'NC-0007', 'service' => 'Soudure']), $task->label);
        $this->assertTrue($nc->reworkTasks()->whereKey($task->id)->exists());

        $log = Activity::where('description', 'nc_rework_ordered')->latest('id')->first();
        $this->assertSame($nc->id, (int) $log->subject_id);
        $this->assertSame([$task->id], $log->properties['tasks']);
    }

    public function test_the_order_line_is_left_unchanged_by_the_rework(): void
    {
        $this->grant();
        // Prix calculé : la gamme fait le prix, une retouche ne doit pas l'augmenter.
        $line = $this->line(['use_calculated_price' => true]);
        Task::factory()->create([
            'order_lines_id' => $line->id, 'quote_lines_id' => null, 'products_id' => null, 'sub_assembly_id' => null,
            'methods_services_id' => $this->welding->id, 'type' => 1, 'ordre' => 10, 'label' => 'Soudure',
            'status_id' => $this->statuses['Finished'], 'unit_price' => 80, 'unit_cost' => 50, 'planned_qty' => null,
        ]);
        $before = $line->fresh();
        $priceBefore = $before->selling_price;

        $this->service()->createReworkOrder($this->nc($line), [
            ['methods_services_id' => $this->welding->id, 'hours' => 3],
        ], $this->user);

        $after = $line->fresh();
        $this->assertEquals($priceBefore, $after->selling_price);
        foreach (['qty', 'discount', 'delivered_qty', 'invoiced_qty', 'delivery_status', 'invoice_status', 'delivery_date'] as $field) {
            $this->assertEquals($before->getRawOriginal($field), $after->getRawOriginal($field), $field);
        }
        $this->assertEquals($before->getRawOriginal('selling_price'), $after->getRawOriginal('selling_price'));
        // La ligne repasse « en cours » : une tâche reste à faire (statut des tâches, pas un montant).
        $this->assertSame(3, (int) $after->tasks_status);
    }

    public function test_a_rework_order_requires_the_permission(): void
    {
        $nc = $this->nc($this->line());

        $this->postJson(route('quality.nonConformitie.api.rework', $nc->id), ['operations' => [
            ['methods_services_id' => $this->welding->id, 'hours' => 1],
        ]])->assertForbidden();

        // Le service refuse aussi une saisie à l'écran sans le droit…
        try {
            $this->service()->createReworkOrder($nc, [['methods_services_id' => $this->welding->id]], $this->user);
            $this->fail('Refus attendu');
        } catch (NonConformityImpactException $e) {
            $this->assertSame(403, $e->getCode());
        }
        $this->assertSame(0, Task::where('quality_non_conformity_id', $nc->id)->count());

        // … mais le connecteur Siteflow passe sans utilisateur.
        $tasks = $this->service()->createReworkOrder($nc, [['methods_services_id' => $this->welding->id, 'hours' => 1]], null, NonConformityImpact::SOURCE_SITEFLOW);
        $this->assertCount(1, $tasks);
    }

    public function test_a_rework_order_is_refused_without_an_order_line_or_a_valid_service(): void
    {
        $this->grant();

        $this->postJson(route('quality.nonConformitie.api.rework', $this->nc(null, ['code' => 'NC-0008'])->id), ['operations' => [
            ['methods_services_id' => $this->welding->id, 'hours' => 1],
        ]])->assertStatus(422)->assertJsonPath('message', __('nc_impact.errors.no_order_line'));

        $material = MethodsServices::factory()->create(['type' => MethodsServices::TYPE_RAW_MATERIAL]);
        $nc = $this->nc($this->line());
        $this->postJson(route('quality.nonConformitie.api.rework', $nc->id), ['operations' => [
            ['methods_services_id' => $material->id, 'hours' => 1],
        ]])->assertStatus(422)->assertJsonPath('message', __('nc_impact.errors.invalid_service'));

        $nc->update(['statu' => NonConformityImpact::STATU_CANCELED]);
        $this->postJson(route('quality.nonConformitie.api.rework', $nc->id), ['operations' => [
            ['methods_services_id' => $this->welding->id, 'hours' => 1],
        ]])->assertStatus(422);

        $this->assertSame(0, Task::whereNotNull('quality_non_conformity_id')->count());
    }

    public function test_the_rework_task_follows_the_production_release_and_phase_sign_off(): void
    {
        config(['production.require_release' => true]);
        $signOff = MethodsServices::factory()->create(['type' => 1, 'requires_release' => true, 'hourly_rate' => 40]);
        $line = $this->line();

        $tasks = $this->service()->createReworkOrder($this->nc($line), [
            ['methods_services_id' => $signOff->id, 'hours' => 1],
        ], null, NonConformityImpact::SOURCE_SITEFLOW);

        // Ligne sans BPF : la retouche ne démarre pas plus qu'une autre opération.
        $this->assertNotNull(app(ProductionRelease::class)->blockingReason($tasks->first()));

        // Poste à visa : la retouche terminée attend son visa comme toute phase.
        $task = $tasks->first();
        $task->update(['status_id' => $this->statuses['Finished']]);
        $this->user->givePermissionTo(TaskRelease::PERMISSION);
        app(TaskRelease::class)->release($task->fresh()->load('status'), $this->user, TaskRelease::SOURCE_MANUAL);
        $this->assertNotNull($task->fresh()->released_at);
    }

    public function test_a_replicated_rework_task_is_no_longer_a_rework(): void
    {
        $tasks = $this->service()->createReworkOrder($this->nc($this->line()), [
            ['methods_services_id' => $this->welding->id, 'hours' => 1],
        ], null, NonConformityImpact::SOURCE_SITEFLOW);

        $copy = $tasks->first()->replicate();
        $copy->save();

        $this->assertNull($copy->fresh()->quality_non_conformity_id);
    }

    // ------------------------------------------------------------------
    // Coûts
    // ------------------------------------------------------------------

    public function test_the_actual_internal_cost_comes_from_the_time_logs(): void
    {
        $line = $this->line();
        $nc = $this->nc($line, ['cost_external' => 30, 'cost_scrap' => 12.5]);
        $task = $this->service()->createReworkOrder($nc, [
            ['methods_services_id' => $this->welding->id, 'hours' => 2],
        ], null, NonConformityImpact::SOURCE_SITEFLOW)->first();

        $this->log($task, '2026-11-05 08:00:00', '2026-11-05 09:00:00');
        $this->log($task, '2026-11-05 14:00:00', '2026-11-05 14:30:00');

        $impact = $this->getJson(route('quality.nonConformitie.api.impact', $nc->id))->assertOk()->json('impact');

        // 1,5 h × 60 €/h
        $this->assertEquals(90, $impact['costs']['internal_actual']);
        $this->assertNull($impact['costs']['internal']);
        $this->assertEquals(132.5, $impact['costs']['total']);
        $this->assertEquals(1.5, $impact['rework'][0]['actual_hours']);
        $this->assertEquals(2, $impact['rework'][0]['planned_hours']);

        // Coût interne saisi : il prime sur le réel, qui reste affiché à côté.
        $this->postJson(route('quality.nonConformitie.api.impact.update', $nc->id), [
            'cost_internal' => '200', 'cost_external' => 30, 'cost_scrap' => 12.5, 'delay_days' => 3,
            'impact_comment' => 'Reprise de 2 pièces',
        ])->assertOk()
            ->assertJsonPath('impact.costs.internal', 200)
            ->assertJsonPath('impact.costs.internal_actual', 90)
            ->assertJsonPath('impact.costs.total', 242.5)
            ->assertJsonPath('impact.delay_days', 3)
            ->assertJsonPath('impact.order_line.proposed_delivery_date', '2026-12-03');

        // Journal des modifications qualité
        $change = Activity::forSubject($nc)->where('event', 'updated')->latest('id')->first();
        $this->assertEquals(200, $change->properties['attributes']['cost_internal']);
    }

    public function test_impact_fields_are_validated(): void
    {
        $nc = $this->nc($this->line());

        $this->postJson(route('quality.nonConformitie.api.impact.update', $nc->id), ['cost_scrap' => -5])
            ->assertStatus(422)->assertJsonValidationErrors('cost_scrap');
        $this->postJson(route('quality.nonConformitie.api.impact.update', $nc->id), ['delay_days' => 'abc'])
            ->assertStatus(422)->assertJsonValidationErrors('delay_days');
    }

    // ------------------------------------------------------------------
    // Délai
    // ------------------------------------------------------------------

    public function test_the_delay_is_only_proposed_until_applied(): void
    {
        $this->grant();
        $line = $this->line();
        $nc = $this->nc($line, ['delay_days' => 5]);

        $impact = $this->service()->impact($nc);
        $this->assertSame('2026-12-05', $impact['order_line']['proposed_delivery_date']);
        // Rien n'est reporté d'office.
        $this->assertSame('2026-11-30', Carbon::parse($line->fresh()->delivery_date)->toDateString());
        $this->assertSame(0, OrderLineDelayHistory::where('order_lines_id', $line->id)->count());
    }

    public function test_applying_the_delay_postpones_the_line_and_traces_it(): void
    {
        $this->grant();
        $line = $this->line();
        $promised = $line->fresh()->promised_delivery_date;
        $nc = $this->nc($line, ['delay_days' => 5]);

        $this->postJson(route('quality.nonConformitie.api.delay', $nc->id))->assertOk()
            ->assertJsonPath('impact.order_line.delivery_date', '2026-12-05');

        $line->refresh();
        $this->assertSame('2026-12-05', Carbon::parse($line->delivery_date)->toDateString());
        // La date promise au client ne bouge jamais.
        $this->assertEquals($promised, $line->promised_delivery_date);

        $history = OrderLineDelayHistory::where('order_lines_id', $line->id)->sole();
        $this->assertSame(OrderLineDelayHistory::FIELD_DELIVERY_DATE, $history->field);
        $this->assertSame(OrderLineDelayHistory::ORIGIN_NON_CONFORMITY, $history->origin);
        $this->assertSame('NC NC-0007', $history->reason);
        $this->assertStringStartsWith('2026-11-30', (string) $history->old_value);
        $this->assertStringStartsWith('2026-12-05', (string) $history->new_value);

        // Une date choisie par l'utilisateur remplace la proposition.
        $this->postJson(route('quality.nonConformitie.api.delay', $nc->id), ['delivery_date' => '2026-12-20'])->assertOk();
        $this->assertSame('2026-12-20', Carbon::parse($line->fresh()->delivery_date)->toDateString());
    }

    public function test_applying_the_delay_requires_the_permission_and_a_date(): void
    {
        $nc = $this->nc($this->line());

        $this->postJson(route('quality.nonConformitie.api.delay', $nc->id))->assertForbidden();

        $this->grant();
        $this->postJson(route('quality.nonConformitie.api.delay', $nc->id))
            ->assertStatus(422)->assertJsonPath('message', __('nc_impact.errors.no_date'));
    }

    // ------------------------------------------------------------------
    // Indicateur coût de non-qualité
    // ------------------------------------------------------------------

    public function test_non_quality_cost_by_month_and_by_supplier(): void
    {
        $supplier = Companies::factory()->create(['statu_supplier' => 2, 'statu_customer' => 1, 'label' => 'Acier Plus']);

        Carbon::setTestNow('2026-09-12 10:00:00');
        $this->nc(null, ['code' => 'NC-S1', 'type' => 2, 'companie_id' => $supplier->id, 'cost_external' => 100, 'cost_scrap' => 50]);
        Carbon::setTestNow('2026-11-02 10:00:00');
        $this->nc(null, ['code' => 'NC-S2', 'type' => 2, 'companie_id' => $supplier->id, 'cost_internal' => 40]);
        // Client (non fournisseur) : compté par mois, pas par fournisseur.
        $this->nc(null, ['code' => 'NC-C1', 'type' => 2, 'companie_id' => $this->customer->id, 'cost_scrap' => 10]);
        // NC annulée : jamais comptée.
        $this->nc(null, ['code' => 'NC-X', 'statu' => 4, 'companie_id' => $supplier->id, 'cost_external' => 1000]);
        // Coût réel d'une retouche sans coût interne saisi : 2 h × 60 €.
        $task = $this->service()->createReworkOrder($this->nc($this->line(), ['code' => 'NC-R1']), [
            ['methods_services_id' => $this->welding->id, 'hours' => 2],
        ], null, NonConformityImpact::SOURCE_SITEFLOW)->first();
        $this->log($task, '2026-11-03 08:00:00', '2026-11-03 10:00:00');
        Carbon::setTestNow('2026-11-08 10:00:00');

        $stats = $this->getJson(route('quality.nonConformitie.api.cost-stats'))->assertOk()->json();

        $this->assertCount(12, $stats['months']);
        $this->assertSame('2025-12', $stats['months'][0]['month']);
        $months = collect($stats['months'])->keyBy('month');
        $this->assertEquals(150, $months['2026-09']['total']);
        $this->assertSame(3, $months['2026-11']['count']);
        // NC-S2 : 40 saisi ; NC-R1 : 120 réel ; NC-C1 : 10 de rebut
        $this->assertEquals(160, $months['2026-11']['internal']);
        $this->assertEquals(10, $months['2026-11']['scrap']);
        $this->assertEquals(170, $months['2026-11']['total']);
        $this->assertEquals(320, $stats['total']);

        $this->assertCount(1, $stats['suppliers']);
        $this->assertSame('Acier Plus', $stats['suppliers'][0]['label']);
        $this->assertSame(2, $stats['suppliers'][0]['count']);
        $this->assertEquals(190, $stats['suppliers'][0]['total']);
    }
}
