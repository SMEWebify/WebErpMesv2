<?php

namespace Tests\Feature\Quality;

use App\Models\Quality\ProcessDiagram;
use App\Models\Quality\QualityAction;
use App\Models\Quality\QualityCause;
use App\Models\Quality\QualityNonConformity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * Journal des modifications des enregistrements qualité (ISO 19443) : seuls les
 * champs modifiés, ancienne et nouvelle valeur, jamais les horodatages ; lisible
 * sur la fiche NC (JSON) et la fiche action (Blade).
 */
class QualityActivityLogTest extends TestCase
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

        $this->user = User::factory()->create(['name' => 'Alice Qualité']);
        $this->actingAs($this->user);
    }

    private function nonConformity(array $attributes = []): QualityNonConformity
    {
        return QualityNonConformity::create($attributes + [
            'code' => 'NC-1', 'label' => 'Rayure', 'statu' => 1, 'type' => 2, 'user_id' => $this->user->id,
        ]);
    }

    public function test_updating_a_non_conformity_logs_the_old_and_new_status(): void
    {
        $nc = $this->nonConformity();

        $this->postJson(route('quality.nonConformitie.api.update', $nc->id), [
            'label' => 'Rayure', 'statu' => 3,
        ])->assertOk();

        $log = Activity::forSubject($nc)->where('event', 'updated')->sole();

        $this->assertSame($this->user->id, (int) $log->causer_id);
        $this->assertSame(1, (int) $log->properties['old']['statu']);
        $this->assertSame(3, (int) $log->properties['attributes']['statu']);
        // Seulement les champs modifiés, jamais les horodatages.
        $this->assertArrayNotHasKey('label', $log->properties['attributes']);
        $this->assertArrayNotHasKey('updated_at', $log->properties['attributes']);
        $this->assertArrayNotHasKey('created_at', $log->properties['attributes']);
    }

    public function test_the_creation_is_logged_and_an_unchanged_save_is_not(): void
    {
        $nc = $this->nonConformity();
        $this->assertSame(1, Activity::forSubject($nc)->where('event', 'created')->count());

        $nc->touch();
        $nc->label = 'Rayure';
        $nc->save();

        $this->assertSame(0, Activity::forSubject($nc)->where('event', 'updated')->count());
    }

    public function test_closing_a_non_conformity_is_logged(): void
    {
        $nc = $this->nonConformity();

        $this->postJson(route('quality.nonConformitie.api.close', $nc->id))->assertOk();

        $log = Activity::forSubject($nc)->where('event', 'updated')->sole();
        $this->assertSame(1, (int) $log->properties['old']['statu']);
        $this->assertSame(3, (int) $log->properties['attributes']['statu']);
        $this->assertNull($log->properties['old']['resolution_date']);
        $this->assertNotNull($log->properties['attributes']['resolution_date']);
    }

    public function test_the_non_conformity_history_is_readable_on_its_card(): void
    {
        $cause = QualityCause::create(['code' => 'C1', 'label' => 'Outil usé']);
        $nc = $this->nonConformity();

        $this->postJson(route('quality.nonConformitie.api.update', $nc->id), [
            'label' => 'Rayure', 'statu' => 3, 'causes_id' => $cause->id,
        ])->assertOk();

        $entries = $this->getJson(route('quality.nonConformitie.api.history', $nc->id))
            ->assertOk()
            ->json('data');

        $this->assertCount(2, $entries);
        $latest = $entries[0];
        $this->assertSame('Alice Qualité', $latest['causer']);
        $this->assertSame(__('activity_history.events.updated'), $latest['event']);

        $changes = collect($latest['changes'])->keyBy('field');
        $this->assertSame(__('activity_history.fields.statu'), $changes['statu']['label']);
        $this->assertSame(__('general_content.in_progress_trans_key'), $changes['statu']['old']);
        $this->assertSame(__('general_content.solved_trans_key'), $changes['statu']['new']);
        $this->assertNull($changes['causes_id']['old']);
        $this->assertSame('Outil usé', $changes['causes_id']['new']);
    }

    public function test_updating_an_action_is_logged_and_shown_on_the_actions_screen(): void
    {
        $action = QualityAction::create([
            'code' => 'ACT-1', 'label' => 'Former', 'statu' => 1, 'type' => 1,
            'user_id' => $this->user->id, 'color' => '#000000',
        ]);

        $this->post(route('quality.action.update', $action->id), [
            'id' => $action->id, 'label' => 'Former', 'statu' => 3, 'type_update' => 1,
            'user_id' => $this->user->id, 'color' => '#000000',
            'pb_descp' => 'Opérateur non formé', 'cause' => 'Arrivée récente', 'action' => 'Formation',
            'quality_non_conformitie_id' => null,
        ])->assertRedirect();

        $log = Activity::forSubject($action)->where('event', 'updated')->sole();
        $this->assertSame(1, (int) $log->properties['old']['statu']);
        $this->assertSame(3, (int) $log->properties['attributes']['statu']);
        $this->assertSame('Formation', $log->properties['attributes']['action']);

        $this->get(route('quality.action'))
            ->assertOk()
            ->assertSee(__('activity_history.title'))
            ->assertSee(__('general_content.validate_trans_key'))
            ->assertSee('Formation');
    }

    public function test_renaming_a_cause_is_logged(): void
    {
        $cause = QualityCause::create(['code' => 'C1', 'label' => 'Outil usé']);
        $cause->update(['label' => 'Outil émoussé']);

        $log = Activity::forSubject($cause)->where('event', 'updated')->sole();
        $this->assertSame('Outil usé', $log->properties['old']['label']);
        $this->assertSame('Outil émoussé', $log->properties['attributes']['label']);
    }

    public function test_a_process_diagram_logs_its_name_but_not_its_graph(): void
    {
        $diagram = ProcessDiagram::create([
            'name' => 'Achats', 'nodes' => [['id' => 'a']], 'edges' => [], 'created_by' => $this->user->id,
        ]);

        $diagram->update(['nodes' => [['id' => 'a'], ['id' => 'b']]]);
        $this->assertSame(0, Activity::forSubject($diagram)->where('event', 'updated')->count());

        $diagram->update(['name' => 'Achats et sous-traitance']);
        $log = Activity::forSubject($diagram)->where('event', 'updated')->sole();
        $this->assertSame(['name' => 'Achats et sous-traitance'], $log->properties['attributes']);
    }
}
