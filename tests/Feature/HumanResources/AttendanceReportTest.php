<?php

namespace Tests\Feature\HumanResources;

use App\Models\Attendance;
use App\Models\User;
use App\Services\HumanResources\AttendanceAggregator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The attendance screen shares its punch pairing with the payroll export.
 * These cover the screen so the shared aggregator cannot regress it.
 */
class AttendanceReportTest extends TestCase
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

    /** @test */
    public function worked_time_is_counted_forward_not_backward()
    {
        $user = User::factory()->create();

        Attendance::create(['user_id' => $user->id, 'punched_at' => '2026-07-13 08:00:00', 'direction' => 'in']);
        Attendance::create(['user_id' => $user->id, 'punched_at' => '2026-07-13 12:00:00', 'direction' => 'out']);

        $report = app(AttendanceAggregator::class)->fromPunches();

        // 4 hours, positive: Carbon 3 returns a signed difference and the naive
        // call order used to yield -14400.
        $this->assertSame(14400, $report[$user->id]['total_seconds']);
        $this->assertSame(0, $report[$user->id]['anomalies']);
        $this->assertSame(1, $report[$user->id]['days']);
    }

    /** @test */
    public function two_consecutive_punch_ins_are_reported_as_an_anomaly()
    {
        $user = User::factory()->create();

        Attendance::create(['user_id' => $user->id, 'punched_at' => '2026-07-13 08:00:00', 'direction' => 'in']);
        Attendance::create(['user_id' => $user->id, 'punched_at' => '2026-07-13 09:00:00', 'direction' => 'in']);
        Attendance::create(['user_id' => $user->id, 'punched_at' => '2026-07-13 12:00:00', 'direction' => 'out']);

        $report = app(AttendanceAggregator::class)->fromPunches();

        $this->assertSame(1, $report[$user->id]['anomalies']);
        // The session is measured from the second punch in.
        $this->assertSame(10800, $report[$user->id]['total_seconds']);
    }

    /** @test */
    public function the_report_screen_renders_and_keeps_a_row_for_a_filtered_employee_with_no_punch()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('human.resources.attendance', ['user_id' => $user->id]))
            ->assertOk()
            ->assertViewIs('admin.human-resources-attendance')
            ->assertViewHas('AttendancePunchReport')
            ->assertSee('00:00');
    }

    /** @test */
    public function the_report_screen_shows_a_positive_duration()
    {
        $user = User::factory()->create();

        Attendance::create(['user_id' => $user->id, 'punched_at' => '2026-07-13 08:00:00', 'direction' => 'in']);
        Attendance::create(['user_id' => $user->id, 'punched_at' => '2026-07-13 15:30:00', 'direction' => 'out']);

        $this->actingAs($user)
            ->get(route('human.resources.attendance'))
            ->assertOk()
            ->assertSee('07:30');
    }
}
