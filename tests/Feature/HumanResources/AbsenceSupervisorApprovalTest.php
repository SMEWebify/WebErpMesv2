<?php

namespace Tests\Feature\HumanResources;

use App\Models\Times\TimesAbsence;
use App\Models\User;
use App\Notifications\AbsenceRequestNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Le responsable direct (users.supervisor_id) valide ou refuse les demandes à
 * valider de son équipe ; il ne modifie ni les dates ni une demande déjà traitée.
 */
class AbsenceSupervisorApprovalTest extends TestCase
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

    private function absence(array $overrides = []): TimesAbsence
    {
        return TimesAbsence::create($overrides + [
            'user_id' => $this->employee->id,
            'absence_type' => 1,
            'absence_type_day' => 3,
            'statu' => 1,
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-06',
        ]);
    }

    /** @test */
    public function the_supervisor_approves_a_pending_request_and_the_employee_is_notified()
    {
        Notification::fake();
        $absence = $this->absence();

        $this->actingAs($this->supervisor)
            ->post(route('times.absence.decide', ['id' => $absence->id]), ['statu' => 2])
            ->assertRedirect();

        $absence->refresh();
        $this->assertSame(2, (int) $absence->statu);
        $this->assertSame('2026-11-02', substr((string) $absence->start_date, 0, 10));

        Notification::assertSentTo($this->employee, AbsenceRequestNotification::class,
            fn ($n, $channels, $notifiable) => $n->toArray($notifiable)['event'] === AbsenceRequestNotification::APPROVED);
    }

    /** @test */
    public function the_supervisor_refuses_a_pending_request()
    {
        $absence = $this->absence();

        $this->actingAs($this->supervisor)
            ->post(route('times.absence.decide', ['id' => $absence->id]), ['statu' => 3])
            ->assertRedirect();

        $this->assertSame(3, (int) $absence->fresh()->statu);
    }

    /** @test */
    public function another_user_cannot_decide()
    {
        $absence = $this->absence();

        $this->actingAs(User::factory()->create())
            ->post(route('times.absence.decide', ['id' => $absence->id]), ['statu' => 2])
            ->assertForbidden();

        $this->assertSame(1, (int) $absence->fresh()->statu);
    }

    /** @test */
    public function the_supervisor_cannot_overturn_a_decided_request()
    {
        $absence = $this->absence(['statu' => 3]);

        $this->actingAs($this->supervisor)
            ->post(route('times.absence.decide', ['id' => $absence->id]), ['statu' => 2])
            ->assertForbidden();

        $this->assertSame(3, (int) $absence->fresh()->statu);
    }

    /** @test */
    public function nobody_but_hr_approves_their_own_request()
    {
        $this->supervisor->update(['supervisor_id' => $this->supervisor->id]);
        $own = $this->absence(['user_id' => $this->supervisor->id]);

        $this->actingAs($this->supervisor)
            ->post(route('times.absence.decide', ['id' => $own->id]), ['statu' => 2])
            ->assertForbidden();

        $this->assertSame(1, (int) $own->fresh()->statu);
    }

    /** @test */
    public function hr_can_still_decide_any_request()
    {
        $absence = $this->absence(['statu' => 2]);

        $this->actingAs($this->hr)
            ->post(route('times.absence.decide', ['id' => $absence->id]), ['statu' => 3])
            ->assertRedirect();

        $this->assertSame(3, (int) $absence->fresh()->statu);
    }

    /** @test */
    public function only_approval_or_refusal_is_accepted()
    {
        $absence = $this->absence();

        $this->actingAs($this->supervisor)
            ->post(route('times.absence.decide', ['id' => $absence->id]), ['statu' => 1])
            ->assertSessionHasErrors('statu');
    }

    /** @test */
    public function the_supervisor_profile_lists_the_team_requests_to_approve()
    {
        $this->absence(['comment' => 'Mariage du cousin']);
        $this->absence(['statu' => 2, 'comment' => 'Deja traitee']);

        $this->actingAs($this->supervisor)
            ->get(route('user.profile', ['id' => $this->supervisor->id]))
            ->assertOk()
            ->assertSee(__('general_content.absence_team_requests_trans_key'))
            ->assertSee('Mariage du cousin')
            ->assertDontSee('Deja traitee');
    }

    /** @test */
    public function a_supervisor_notification_leads_to_the_team_requests()
    {
        $this->actingAs($this->supervisor);
        $present = AbsenceRequestNotification::present(['event' => AbsenceRequestNotification::REQUESTED, 'employee_id' => $this->employee->id]);
        $this->assertStringEndsWith('#LeaveRequest', $present['route']);

        $this->actingAs($this->hr);
        $present = AbsenceRequestNotification::present(['event' => AbsenceRequestNotification::REQUESTED, 'employee_id' => $this->employee->id]);
        $this->assertSame(route('times'), $present['route']);
    }
}
