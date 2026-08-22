<?php

namespace Tests\Feature\HumanResources;

use App\Models\HumanResources\TrainingType;
use App\Models\Methods\MethodsRessources;
use App\Models\OSH\OSHFormation;
use App\Models\Planning\Task;
use App\Models\User;
use App\Services\HumanResources\HabilitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SkillsMatrixTest extends TestCase
{
    use RefreshDatabase;

    private HabilitationService $habilitations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);

        config()->set('hr.habilitation_warning_days', 60);
        Permission::findOrCreate('osh-menu');

        $this->habilitations = app(HabilitationService::class);
    }

    private function caces(): TrainingType
    {
        return TrainingType::create(['code' => 'CACES3', 'label' => 'CACES 3', 'validity_months' => 60]);
    }

    private function train(User $user, TrainingType $type, ?string $expiresAt, int $obtained = 1, string $date = '2024-01-10'): OSHFormation
    {
        return OSHFormation::create([
            'user_id' => $user->id,
            'training_type_id' => $type->id,
            'type_of_training' => $type->label,
            'training_date' => $date,
            'expiration_date' => $expiresAt,
            'certification_obtained' => $obtained,
        ]);
    }

    /** @test */
    public function it_grades_an_authorisation_as_valid_expiring_expired_or_missing()
    {
        $type = $this->caces();
        $now = Carbon::parse('2026-08-16');

        $valid = User::factory()->create();
        $expiring = User::factory()->create();
        $expired = User::factory()->create();
        $failed = User::factory()->create();
        $missing = User::factory()->create();

        $this->train($valid, $type, '2027-06-30');
        $this->train($expiring, $type, '2026-09-30');   // within the 60 day window
        $this->train($expired, $type, '2026-07-01');
        $this->train($failed, $type, '2027-06-30', obtained: 2);

        $matrix = $this->habilitations->matrix([$valid->id, $expiring->id, $expired->id, $failed->id, $missing->id], null, $now);

        $this->assertSame(HabilitationService::STATUS_VALID, $matrix[$valid->id][$type->id]['status']);
        $this->assertSame(HabilitationService::STATUS_EXPIRING, $matrix[$expiring->id][$type->id]['status']);
        $this->assertSame(HabilitationService::STATUS_EXPIRED, $matrix[$expired->id][$type->id]['status']);
        $this->assertSame(HabilitationService::STATUS_FAILED, $matrix[$failed->id][$type->id]['status']);
        $this->assertSame(HabilitationService::STATUS_MISSING, $matrix[$missing->id][$type->id]['status']);
    }

    /** @test */
    public function a_renewal_supersedes_the_expired_session()
    {
        $type = $this->caces();
        $user = User::factory()->create();

        $this->train($user, $type, '2026-01-31', date: '2021-01-10');
        $this->train($user, $type, '2031-01-31', date: '2026-01-10');

        $matrix = $this->habilitations->matrix([$user->id], null, Carbon::parse('2026-08-16'));

        $this->assertSame(HabilitationService::STATUS_VALID, $matrix[$user->id][$type->id]['status']);
    }

    /** @test */
    public function a_training_with_no_expiry_date_never_expires()
    {
        $type = TrainingType::create(['code' => 'SST', 'label' => 'Sauveteur secouriste', 'validity_months' => 0]);
        $user = User::factory()->create();

        $this->train($user, $type, null);

        $matrix = $this->habilitations->matrix([$user->id], null, Carbon::parse('2030-01-01'));

        $this->assertSame(HabilitationService::STATUS_VALID, $matrix[$user->id][$type->id]['status']);
    }

    /** @test */
    public function it_reports_a_task_whose_operator_lacks_the_authorisation_of_the_machine()
    {
        $type = $this->caces();
        $resource = MethodsRessources::factory()->create();
        $type->resources()->attach($resource->id);

        $operator = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $operator->id]);
        $task->resources()->attach($resource->id);

        $alerts = $this->habilitations->taskAlerts(Carbon::parse('2026-08-16'));

        $this->assertCount(1, $alerts);
        $this->assertSame($task->id, $alerts[0]['task']->id);
        $this->assertSame(HabilitationService::STATUS_MISSING, $alerts[0]['status']);
    }

    /** @test */
    public function a_qualified_operator_raises_no_alert()
    {
        $type = $this->caces();
        $resource = MethodsRessources::factory()->create();
        $type->resources()->attach($resource->id);

        $operator = User::factory()->create();
        $this->train($operator, $type, '2030-01-01');

        $task = Task::factory()->create(['user_id' => $operator->id]);
        $task->resources()->attach($resource->id);

        $this->assertSame([], $this->habilitations->taskAlerts(Carbon::parse('2026-08-16')));
    }

    /**
     * The whole point of the feature: reporting a gap must never stop the shop
     * floor. Assigning and saving the task goes through untouched.
     *
     * @test
     */
    public function a_missing_authorisation_does_not_prevent_assigning_the_task()
    {
        $type = $this->caces();
        $resource = MethodsRessources::factory()->create();
        $type->resources()->attach($resource->id);

        $operator = User::factory()->create();
        $task = Task::factory()->create(['user_id' => null]);
        $task->resources()->attach($resource->id);

        $task->user_id = $operator->id;

        $this->assertTrue($task->save());
        $this->assertSame($operator->id, $task->fresh()->user_id);
        $this->assertCount(1, $this->habilitations->taskAlerts(Carbon::parse('2026-08-16')));
    }

    /** @test */
    public function the_matrix_screen_renders()
    {
        $type = $this->caces();
        $user = User::factory()->create();
        $user->givePermissionTo('osh-menu');
        $this->train($user, $type, '2030-01-01');

        $this->actingAs($user)
            ->get(route('osh.skills.matrix'))
            ->assertOk()
            ->assertViewIs('osh.osh-skills-matrix')
            ->assertViewHas('Matrix')
            ->assertSee('CACES3');
    }

    /** @test */
    public function creating_an_authorisation_type_requires_the_osh_permission()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('osh.training.type.store'), ['code' => 'PONT', 'label' => 'Pontier'])
            ->assertForbidden();

        $this->assertSame(0, TrainingType::count());
    }
}
