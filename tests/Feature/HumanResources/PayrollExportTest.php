<?php

namespace Tests\Feature\HumanResources;

use App\Models\Attendance;
use App\Models\HumanResources\LeaveType;
use App\Models\Times\TimesAbsence;
use App\Models\User;
use App\Services\HumanResources\LeaveBalanceService;
use App\Services\HumanResources\PayrollExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PayrollExportTest extends TestCase
{
    use RefreshDatabase;

    private PayrollExportService $payroll;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);

        Cache::forget('hr_bank_holidays');
        Permission::findOrCreate('human-resources-menu');

        $this->payroll = app(PayrollExportService::class);
    }

    private function paidLeave(): LeaveType
    {
        return LeaveType::create(['code' => 'CP', 'label' => 'Congés payés', 'counts_against_balance' => true]);
    }

    /** @test */
    public function it_exports_approved_absences_as_days_under_the_leave_type_code()
    {
        $type = $this->paidLeave();
        $user = User::factory()->create(['payroll_number' => 'M0042']);

        // Monday 13 to Friday 17 July 2026: 5 working days.
        TimesAbsence::create([
            'user_id' => $user->id,
            'leave_type_id' => $type->id,
            'absence_type' => LeaveBalanceService::DURATION_FULL_DAY,
            'absence_type_day' => LeaveBalanceService::DAY_WORKED,
            'statu' => LeaveBalanceService::STATUS_APPROVED,
            'start_date' => '2026-07-13',
            'end_date' => '2026-07-17',
        ]);

        $rows = $this->payroll->rows('2026-07-01');
        $line = $rows->firstWhere('matricule', 'M0042');

        $this->assertNotNull($line);
        $this->assertSame('CP', $line['code']);
        $this->assertSame('J', $line['unit']);
        $this->assertEquals(5.0, $line['quantity']);
        $this->assertSame('2026-07-01', $line['period_start']);
        $this->assertSame('2026-07-31', $line['period_end']);
    }

    /** @test */
    public function a_pending_absence_is_never_exported()
    {
        $type = $this->paidLeave();
        $user = User::factory()->create(['payroll_number' => 'M0042']);

        TimesAbsence::create([
            'user_id' => $user->id,
            'leave_type_id' => $type->id,
            'absence_type' => LeaveBalanceService::DURATION_FULL_DAY,
            'absence_type_day' => LeaveBalanceService::DAY_WORKED,
            'statu' => LeaveBalanceService::STATUS_PENDING,
            'start_date' => '2026-07-13',
            'end_date' => '2026-07-17',
        ]);

        $this->assertTrue($this->payroll->rows('2026-07-01')->isEmpty());
    }

    /** @test */
    public function an_absence_spanning_two_months_is_split_between_them()
    {
        $type = $this->paidLeave();
        $user = User::factory()->create(['payroll_number' => 'M0042']);

        // Monday 27 July to Friday 7 August 2026: 5 working days in July,
        // 5 in August.
        TimesAbsence::create([
            'user_id' => $user->id,
            'leave_type_id' => $type->id,
            'absence_type' => LeaveBalanceService::DURATION_FULL_DAY,
            'absence_type_day' => LeaveBalanceService::DAY_WORKED,
            'statu' => LeaveBalanceService::STATUS_APPROVED,
            'start_date' => '2026-07-27',
            'end_date' => '2026-08-07',
        ]);

        $this->assertEquals(5.0, $this->payroll->rows('2026-07-01')->firstWhere('code', 'CP')['quantity']);
        $this->assertEquals(5.0, $this->payroll->rows('2026-08-01')->firstWhere('code', 'CP')['quantity']);
    }

    /** @test */
    public function it_exports_badged_hours_from_the_punches()
    {
        $user = User::factory()->create(['payroll_number' => 'M0042']);

        Attendance::create(['user_id' => $user->id, 'punched_at' => '2026-07-13 08:00:00', 'direction' => 'in']);
        Attendance::create(['user_id' => $user->id, 'punched_at' => '2026-07-13 12:00:00', 'direction' => 'out']);
        Attendance::create(['user_id' => $user->id, 'punched_at' => '2026-07-14 08:00:00', 'direction' => 'in']);
        Attendance::create(['user_id' => $user->id, 'punched_at' => '2026-07-14 11:30:00', 'direction' => 'out']);

        $line = $this->payroll->rows('2026-07-01')->firstWhere('code', PayrollExportService::CODE_WORKED_HOURS);

        $this->assertNotNull($line);
        $this->assertEquals(7.5, $line['quantity']);
        $this->assertSame('H', $line['unit']);
    }

    /** @test */
    public function it_falls_back_on_the_internal_id_and_warns_when_no_payroll_number_is_set()
    {
        $user = User::factory()->create(['payroll_number' => null]);

        Attendance::create(['user_id' => $user->id, 'punched_at' => '2026-07-13 08:00:00', 'direction' => 'in']);
        Attendance::create(['user_id' => $user->id, 'punched_at' => '2026-07-13 12:00:00', 'direction' => 'out']);

        $line = $this->payroll->rows('2026-07-01')->first();
        $this->assertSame((string) $user->id, $line['matricule']);

        $warnings = collect($this->payroll->warnings('2026-07-01'));
        $this->assertTrue($warnings->contains(fn ($w) => $w['type'] === 'missing_payroll_number'));
    }

    /** @test */
    public function a_forgotten_punch_out_is_flagged_rather_than_silently_dropped()
    {
        $user = User::factory()->create(['payroll_number' => 'M0042']);

        Attendance::create(['user_id' => $user->id, 'punched_at' => '2026-07-13 08:00:00', 'direction' => 'in']);
        // No matching "out".

        $warnings = collect($this->payroll->warnings('2026-07-01'));

        $this->assertTrue($warnings->contains(fn ($w) => $w['type'] === 'attendance_anomaly' && $w['count'] === 1));
    }

    /** @test */
    public function the_export_screen_and_download_are_reserved_to_human_resources()
    {
        $employee = User::factory()->create();
        $hr = User::factory()->create();
        $hr->givePermissionTo('human-resources-menu');

        $this->actingAs($employee)->get(route('human.resources.payroll.export'))->assertForbidden();

        $this->actingAs($hr)
            ->get(route('human.resources.payroll.export'))
            ->assertOk()
            ->assertViewIs('admin.human-resources-payroll-export');
    }

    /** @test */
    public function it_downloads_a_csv_file_named_after_the_period()
    {
        $hr = User::factory()->create();
        $hr->givePermissionTo('human-resources-menu');

        $response = $this->actingAs($hr)
            ->post(route('human.resources.payroll.export.download', ['ext' => 'csv']), ['month' => '2026-07-01']);

        $response->assertOk();
        $this->assertStringContainsString('paie-2026-07.csv', $response->headers->get('content-disposition'));
    }

    /** @test */
    public function an_unknown_extension_is_rejected()
    {
        $hr = User::factory()->create();
        $hr->givePermissionTo('human-resources-menu');

        $this->actingAs($hr)
            ->post(route('human.resources.payroll.export.download', ['ext' => 'exe']), ['month' => '2026-07-01'])
            ->assertNotFound();
    }
}
