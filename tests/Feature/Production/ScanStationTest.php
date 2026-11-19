<?php

namespace Tests\Feature\Production;

use App\Models\Admin\Factory;
use App\Models\Companies\Companies;
use App\Models\Methods\MethodsServices;
use App\Models\Planning\Status;
use App\Models\Planning\Task;
use App\Models\Planning\TaskActivities;
use App\Models\Products\Products;
use App\Models\User;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Services\Production\ScanCode;
use App\Services\Production\ScanStation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Écran de scan atelier (docs/pointage-scan.md, §3) : action d'un scan selon
 * factory.scan_mode.
 */
class ScanStationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private MethodsServices $bending;

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

        \App\Models\Methods\MethodsUnits::factory()->create();
        \App\Models\Methods\MethodsFamilies::factory()->create();
        foreach (['Open', 'Started', 'In progress', 'Finished'] as $order => $title) {
            Status::create(['title' => $title, 'order' => $order]);
        }
        $this->bending = MethodsServices::factory()->create(['code' => 'PLI', 'label' => 'Pliage', 'type' => MethodsServices::TYPE_PRODUCTIVE]);
    }

    private function mode(string $mode): void
    {
        app()->instance('Factory', new Factory(['scan_mode' => $mode]));
    }

    private function task(array $line = []): Task
    {
        $order = Orders::factory()->create(['companies_id' => Companies::factory()->create()->id, 'type' => 1, 'statu' => 1]);
        $orderLine = OrderLines::factory()->create($line + [
            'orders_id' => $order->id, 'product_id' => Products::factory()->create()->id, 'qty' => 10,
            'delivered_qty' => 0, 'invoiced_qty' => 0, 'delivery_status' => 1, 'invoice_status' => 1,
            'line_type' => 'article', 'ind' => 'A',
        ]);

        return Task::factory()->create([
            'order_lines_id' => $orderLine->id, 'quote_lines_id' => null, 'products_id' => null, 'sub_assembly_id' => null,
            'methods_services_id' => $this->bending->id, 'type' => 1, 'ordre' => 20, 'code' => 'PLI',
            'status_id' => Status::where('title', 'Open')->value('id'),
        ]);
    }

    private function scan(Task $task, array $extra = [])
    {
        return $this->postJson(route('workshop.scan.store'), ['code' => ScanCode::forTask($task)] + $extra);
    }

    private function types(Task $task): array
    {
        return TaskActivities::where('task_id', $task->id)->orderBy('id')->pluck('type')->map(fn ($t) => (int) $t)->all();
    }

    public function test_open_mode_only_opens_the_task(): void
    {
        $this->mode('open');
        $task = $this->task();

        $this->scan($task)->assertOk()
            ->assertJson(['result' => 'opened', 'task' => ['id' => $task->id]])
            ->assertJsonPath('url', route('workshop.task.statu.id', ['id' => $task->id]));

        $this->assertSame([], $this->types($task));
    }

    public function test_toggle_starts_then_finishes_and_ignores_a_double_scan(): void
    {
        $this->mode('toggle');
        $task = $this->task();

        $this->scan($task)->assertOk()->assertJson(['result' => 'started']);
        $this->assertSame('In progress', $task->fresh()->status->title);

        // Seconde lecture de la même étiquette dans les 5 s : rien n'est écrit.
        $this->scan($task)->assertOk()->assertJson(['result' => 'ignored']);
        $this->assertSame([TaskActivities::TYPE_START], $this->types($task));

        $this->travel(6)->seconds();
        $this->scan($task)->assertOk()->assertJson(['result' => 'finished']);
        $this->assertSame([TaskActivities::TYPE_START, TaskActivities::TYPE_FINISH], $this->types($task));
        $this->assertSame('Finished', $task->fresh()->status->title);

        $this->travel(6)->seconds();
        $this->scan($task)->assertOk()->assertJson(['result' => 'already_finished']);
        $this->assertCount(2, $this->types($task));
    }

    public function test_a_paused_task_is_resumed(): void
    {
        $this->mode('toggle');
        $task = $this->task();
        TaskActivities::create(['task_id' => $task->id, 'user_id' => $this->user->id, 'type' => TaskActivities::TYPE_START, 'timestamp' => now()->subHour(), 'good_qt' => 0, 'bad_qt' => 0]);
        TaskActivities::create(['task_id' => $task->id, 'user_id' => $this->user->id, 'type' => TaskActivities::TYPE_END, 'timestamp' => now()->subMinutes(30), 'good_qt' => 0, 'bad_qt' => 0]);

        $this->scan($task)->assertOk()->assertJson(['result' => 'resumed']);
    }

    public function test_toggle_qty_asks_for_quantities_before_finishing(): void
    {
        $this->mode('toggle_qty');
        $task = $this->task();

        $this->scan($task)->assertOk()->assertJson(['result' => 'started']);
        $this->travel(6)->seconds();

        $this->scan($task)->assertOk()
            ->assertJson(['result' => 'needs_qty', 'task' => ['remaining_qty' => 10]]);
        $this->assertSame([TaskActivities::TYPE_START], $this->types($task), 'rien n\'est écrit avant la saisie');

        // Validation de la fenêtre aussitôt après : pas un scan en double.
        $this->scan($task, ['good_qty' => 8, 'bad_qty' => 2])->assertOk()
            ->assertJson(['result' => 'finished', 'task' => ['good_qty' => 8, 'bad_qty' => 2]]);
        $this->assertSame([
            TaskActivities::TYPE_START, TaskActivities::TYPE_DECLARE_GOOD, TaskActivities::TYPE_DECLARE_BAD, TaskActivities::TYPE_FINISH,
        ], $this->types($task));
    }

    public function test_a_start_without_engineering_release_is_refused(): void
    {
        config(['production.require_release' => true]);
        $this->mode('toggle');
        $task = $this->task();

        $this->scan($task)->assertOk()->assertJson(['result' => 'refused']);
        $this->assertSame([], $this->types($task));
    }

    public function test_an_unknown_code_is_not_found(): void
    {
        $this->mode('toggle');

        $this->postJson(route('workshop.scan.store'), ['code' => 'N2P:OF999999*999999*PLI*10'])
            ->assertNotFound()->assertJson(['result' => 'not_found']);
    }

    // ------------------------------------------------------------------
    // Badges (étape 2c)
    // ------------------------------------------------------------------

    public function test_a_scanned_badge_names_the_operator_of_the_next_scans(): void
    {
        $this->mode('toggle');
        $operator = User::factory()->create(['name' => 'Arnaud']);
        $operator->forceFill(['badge_code' => 'AB12CD34'])->save();
        $task = $this->task();

        // Douchette en majuscules bloquées ou non : même badge.
        $this->postJson(route('workshop.scan.store'), ['code' => 'n2p-b:ab12cd34'])->assertOk()
            ->assertJson(['result' => 'badge', 'operator' => ['name' => 'Arnaud', 'badge' => 'AB12CD34']]);
        $this->assertSame([], $this->types($task), 'un badge n\'enregistre rien');

        $this->scan($task, ['badge' => 'AB12CD34'])->assertOk()->assertJson(['result' => 'started']);
        $this->assertSame($operator->id, (int) TaskActivities::where('task_id', $task->id)->value('user_id'));
    }

    public function test_an_unknown_badge_is_refused(): void
    {
        $this->mode('toggle');
        $task = $this->task();

        $this->postJson(route('workshop.scan.store'), ['code' => 'N2P-B:INCONNU'])->assertNotFound()
            ->assertJson(['result' => 'badge_unknown']);
        $this->scan($task, ['badge' => 'INCONNU'])->assertNotFound()->assertJson(['result' => 'badge_unknown']);
        $this->assertSame([], $this->types($task));
    }

    public function test_a_kiosk_account_needs_a_badge_and_only_opens_the_scan_screen(): void
    {
        $this->mode('toggle');
        \Spatie\Permission\Models\Permission::findOrCreate(ScanStation::KIOSK_PERMISSION, 'web');
        $this->user->givePermissionTo(ScanStation::KIOSK_PERMISSION);
        $task = $this->task();

        $this->scan($task)->assertOk()->assertJson(['result' => 'badge_required']);
        $this->assertSame([], $this->types($task));

        $this->get(route('workshop.scan'))->assertOk();
        $this->get(route('workshop'))->assertRedirect(route('workshop.scan'));
        $this->getJson(route('production.task.statu.api.show', $task->id))->assertForbidden();
    }

    public function test_human_resources_set_the_badge_and_print_it(): void
    {
        \Spatie\Permission\Models\Permission::findOrCreate('human-resources-menu', 'web');
        $this->user->givePermissionTo('human-resources-menu');
        $employee = User::factory()->create();
        $other = User::factory()->create();
        $other->forceFill(['badge_code' => 'PRIS'])->save();

        // Le formulaire envoie toujours ces champs (colonnes non nulles).
        $update = fn (array $data) => $this->post(route('human.resources.update.user', ['id' => $employee->id]), $data + [
            'employment_status' => $employee->employment_status ?? 1, 'statu' => $employee->statu ?? 1,
        ]);

        $this->get(route('human.resources.user.badge', ['id' => $employee->id]))->assertNotFound();

        $update(['badge_code' => ' ab12cd34 '])->assertSessionHasNoErrors();
        $this->assertSame('AB12CD34', $employee->fresh()->badge_code);

        $update(['badge_code' => 'pris'])->assertSessionHasErrors('badge_code');
        $update(['badge_code' => 'A*B'])->assertSessionHasErrors('badge_code');
        $this->assertSame('AB12CD34', $employee->fresh()->badge_code);

        // Ancien formulaire sans le champ : badge inchangé.
        $update(['job_title' => 'Plieur'])->assertSessionHasNoErrors();
        $this->assertSame('AB12CD34', $employee->fresh()->badge_code);

        $this->get(route('human.resources.user.badge', ['id' => $employee->id]))->assertOk()
            ->assertSee('N2P-B:AB12CD34');

        $this->assertArrayNotHasKey('badge_code', $employee->fresh()->toArray(), 'jamais sérialisé');

        $update(['badge_code' => ''])->assertSessionHasNoErrors();
        $this->assertNull($employee->fresh()->badge_code);
    }

    public function test_the_anonymisation_clears_the_badge(): void
    {
        $employee = User::factory()->create();
        $employee->forceFill(['badge_code' => 'AB12CD34'])->save();

        app(\App\Services\RgpdAnonymizationService::class)->anonymizeUser($employee);

        $this->assertNull($employee->fresh()->badge_code);
    }

    public function test_the_scan_screen_and_the_mode_setting_are_shown(): void
    {
        $this->mode('toggle_qty');

        $this->get(route('workshop.scan'))->assertOk()
            ->assertSee('scan-station-app', false)
            ->assertSee(__('scan_station.modes.toggle_qty'));
    }
}
