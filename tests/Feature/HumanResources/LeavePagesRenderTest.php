<?php

namespace Tests\Feature\HumanResources;

use App\Models\HumanResources\LeaveType;
use App\Models\Times\TimesAbsence;
use App\Models\User;
use App\Services\HumanResources\LeaveBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The employee profile and the time screen both gained a leave type selector,
 * a days column and a document manager: make sure they still render.
 */
class LeavePagesRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);
    }

    private function absenceFor(User $user, LeaveType $type): TimesAbsence
    {
        return TimesAbsence::create([
            'user_id' => $user->id,
            'leave_type_id' => $type->id,
            'absence_type' => LeaveBalanceService::DURATION_FULL_DAY,
            'absence_type_day' => LeaveBalanceService::DAY_WORKED,
            'statu' => LeaveBalanceService::STATUS_APPROVED,
            'start_date' => '2026-07-13',
            'end_date' => '2026-07-17',
        ]);
    }

    /** @test */
    public function the_profile_page_shows_the_leave_balance()
    {
        $type = LeaveType::create(['code' => 'CP', 'label' => 'Congés payés', 'counts_against_balance' => true]);
        $user = User::factory()->create();
        $this->absenceFor($user, $type);

        $this->actingAs($user)
            ->get(route('user.profile', ['id' => $user->id]))
            ->assertOk()
            ->assertViewHas('LeaveSummary')
            ->assertViewHas('LeaveTypes')
            ->assertSee('Congés payés');
    }

    /** @test */
    public function the_time_screen_lists_absences_with_their_leave_type()
    {
        $type = LeaveType::create(['code' => 'CP', 'label' => 'Congés payés', 'counts_against_balance' => true]);
        $user = User::factory()->create();
        $this->absenceFor($user, $type);

        $this->actingAs($user)
            ->get(route('times'))
            ->assertOk()
            ->assertViewHas('LeaveTypes')
            ->assertSee('Congés payés');
    }
}
