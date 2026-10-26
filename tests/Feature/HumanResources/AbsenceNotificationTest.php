<?php

namespace Tests\Feature\HumanResources;

use App\Models\Times\TimesAbsence;
use App\Models\User;
use App\Notifications\AbsenceRequestNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AbsenceNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $hr;
    private User $supervisor;
    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);

        Permission::findOrCreate('human-resources-menu');

        $this->hr = User::factory()->create();
        $this->hr->givePermissionTo('human-resources-menu');
        $this->supervisor = User::factory()->create();
        $this->employee = User::factory()->create(['supervisor_id' => $this->supervisor->id]);
    }

    private function request(array $overrides = []): array
    {
        return $overrides + [
            'absence_type' => 1,
            'absence_type_day' => 3,
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-06',
        ];
    }

    /** @test */
    public function a_new_request_notifies_hr_and_the_supervisor_but_not_the_requester()
    {
        Notification::fake();

        $this->actingAs($this->employee)->post(route('times.absence.create'), $this->request())->assertRedirect();

        Notification::assertSentTo([$this->hr, $this->supervisor], AbsenceRequestNotification::class,
            fn ($n, $channels, $notifiable) => $n->toArray($notifiable)['event'] === AbsenceRequestNotification::REQUESTED);
        Notification::assertNotSentTo($this->employee, AbsenceRequestNotification::class);
    }

    /** @test */
    public function approving_notifies_the_employee_only()
    {
        $absence = TimesAbsence::create($this->request(['user_id' => $this->employee->id, 'statu' => 1]));
        Notification::fake();

        $this->actingAs($this->hr)->post(route('times.absence.update', $absence->id), $this->request([
            'id' => $absence->id,
            'user_id' => $this->employee->id,
            'statu' => 2,
        ]))->assertRedirect();

        Notification::assertSentTo($this->employee, AbsenceRequestNotification::class,
            fn ($n, $channels, $notifiable) => $n->toArray($notifiable)['event'] === AbsenceRequestNotification::APPROVED);
        Notification::assertNotSentTo([$this->hr, $this->supervisor], AbsenceRequestNotification::class);
    }

    /** @test */
    public function amending_a_pending_request_without_a_decision_sends_nothing()
    {
        $absence = TimesAbsence::create($this->request(['user_id' => $this->employee->id, 'statu' => 1]));
        Notification::fake();

        $this->actingAs($this->employee)->post(route('times.absence.update', $absence->id), $this->request([
            'id' => $absence->id,
            'comment' => 'Finalement mardi',
        ]))->assertRedirect();

        Notification::assertNothingSent();
    }

    /** @test */
    public function the_bell_links_a_request_to_the_times_screen()
    {
        $absence = TimesAbsence::create($this->request(['user_id' => $this->employee->id]));
        $this->hr->notify(new AbsenceRequestNotification($absence, AbsenceRequestNotification::REQUESTED, $this->employee->id));
        // Une alerte sans id de document (étalonnage, tâche) ne doit plus casser la cloche.
        $this->hr->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'App\Notifications\QualityDeviceCalibrationNotification',
            'data' => ['code' => 'PAC-01', 'label' => 'Pied à coulisse'],
        ]);

        $html = $this->actingAs($this->hr)->get(route('notifications.get'))->assertOk()->json('dropdown');

        $this->assertStringContainsString(route('times'), $html);
        $this->assertStringContainsString('02/11/2026', $html);
    }
}
