<?php

namespace Tests\Feature\HumanResources;

use App\Models\HumanResources\LeaveBalance;
use App\Models\HumanResources\LeaveType;
use App\Models\Times\TimesAbsence;
use App\Models\Times\TimesBanckHoliday;
use App\Models\User;
use App\Services\HumanResources\LeaveBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LeaveBalanceTest extends TestCase
{
    use RefreshDatabase;

    private LeaveBalanceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // The reference period is the French one: 1 June to 31 May.
        config()->set('hr.leave_period_start_month', 6);
        config()->set('hr.leave_period_start_day', 1);

        Cache::forget('hr_bank_holidays');

        $this->service = app(LeaveBalanceService::class);
    }

    private function paidLeave(): LeaveType
    {
        return LeaveType::create([
            'code' => 'CP',
            'label' => 'Congés payés',
            'counts_against_balance' => true,
            'default_annual_quota' => 25,
        ]);
    }

    /** @test */
    public function it_resolves_the_reference_period_around_the_first_of_june()
    {
        [$start, $end] = $this->service->periodFor(Carbon::parse('2026-08-16'));
        $this->assertSame('2026-06-01', $start->toDateString());
        $this->assertSame('2027-05-31', $end->toDateString());

        [$start, $end] = $this->service->periodFor(Carbon::parse('2026-03-10'));
        $this->assertSame('2025-06-01', $start->toDateString());
        $this->assertSame('2026-05-31', $end->toDateString());
    }

    /** @test */
    public function it_counts_working_days_only_and_skips_bank_holidays()
    {
        // 14 July 2026 is a Tuesday.
        TimesBanckHoliday::create(['fixed' => 1, 'date' => '2026-07-14', 'label' => 'Fête nationale']);
        Cache::forget('hr_bank_holidays');

        $user = User::factory()->create();

        // Monday 13 to Friday 17 July: 5 week days, minus the bank holiday.
        $absence = TimesAbsence::create([
            'user_id' => $user->id,
            'leave_type_id' => $this->paidLeave()->id,
            'absence_type' => LeaveBalanceService::DURATION_FULL_DAY,
            'absence_type_day' => LeaveBalanceService::DAY_WORKED,
            'statu' => LeaveBalanceService::STATUS_APPROVED,
            'start_date' => '2026-07-13',
            'end_date' => '2026-07-17',
        ]);

        $this->assertEquals(4.0, (float) $absence->fresh()->days_count);
    }

    /** @test */
    public function it_charges_half_a_day_for_a_half_day_absence()
    {
        $user = User::factory()->create();

        $absence = TimesAbsence::create([
            'user_id' => $user->id,
            'leave_type_id' => $this->paidLeave()->id,
            'absence_type' => LeaveBalanceService::DURATION_ONE_HALF_DAY,
            'absence_type_day' => LeaveBalanceService::DAY_WORKED,
            'statu' => LeaveBalanceService::STATUS_APPROVED,
            'start_date' => '2026-07-13',
            'end_date' => '2026-07-13',
        ]);

        $this->assertEquals(0.5, (float) $absence->fresh()->days_count);
    }

    /** @test */
    public function it_deducts_approved_and_pending_requests_from_the_entitlement()
    {
        $user = User::factory()->create();
        $type = $this->paidLeave();

        LeaveBalance::create([
            'user_id' => $user->id,
            'leave_type_id' => $type->id,
            'period_start' => '2026-06-01',
            'period_end' => '2027-05-31',
            'entitled_days' => 25,
            'carried_over_days' => 2,
        ]);

        // Approved: Monday 13 to Friday 17 July, 5 working days.
        TimesAbsence::create([
            'user_id' => $user->id,
            'leave_type_id' => $type->id,
            'absence_type' => LeaveBalanceService::DURATION_FULL_DAY,
            'absence_type_day' => LeaveBalanceService::DAY_WORKED,
            'statu' => LeaveBalanceService::STATUS_APPROVED,
            'start_date' => '2026-07-13',
            'end_date' => '2026-07-17',
        ]);

        // Still pending: Monday 24 to Tuesday 25 August, 2 working days.
        TimesAbsence::create([
            'user_id' => $user->id,
            'leave_type_id' => $type->id,
            'absence_type' => LeaveBalanceService::DURATION_FULL_DAY,
            'absence_type_day' => LeaveBalanceService::DAY_WORKED,
            'statu' => LeaveBalanceService::STATUS_PENDING,
            'start_date' => '2026-08-24',
            'end_date' => '2026-08-25',
        ]);

        // Refused requests never move the balance.
        TimesAbsence::create([
            'user_id' => $user->id,
            'leave_type_id' => $type->id,
            'absence_type' => LeaveBalanceService::DURATION_FULL_DAY,
            'absence_type_day' => LeaveBalanceService::DAY_WORKED,
            'statu' => LeaveBalanceService::STATUS_REFUSED,
            'start_date' => '2026-09-07',
            'end_date' => '2026-09-11',
        ]);

        $summary = $this->service->summaryFor($user, Carbon::parse('2026-08-16'));
        $line = $summary['lines'][0];

        $this->assertEquals(27.0, $line['acquired']);
        $this->assertEquals(5.0, $line['taken']);
        $this->assertEquals(2.0, $line['pending']);
        $this->assertEquals(20.0, $line['remaining']);
    }

    /** @test */
    public function a_type_that_does_not_consume_a_balance_reports_no_remaining_days()
    {
        $user = User::factory()->create();
        $type = LeaveType::create([
            'code' => 'MAL',
            'label' => 'Arrêt maladie',
            'counts_against_balance' => false,
        ]);

        TimesAbsence::create([
            'user_id' => $user->id,
            'leave_type_id' => $type->id,
            'absence_type' => LeaveBalanceService::DURATION_FULL_DAY,
            'absence_type_day' => LeaveBalanceService::DAY_WORKED,
            'statu' => LeaveBalanceService::STATUS_APPROVED,
            'start_date' => '2026-07-13',
            'end_date' => '2026-07-17',
        ]);

        $summary = $this->service->summaryFor($user, Carbon::parse('2026-08-16'));
        $line = $summary['lines'][0];

        $this->assertEquals(5.0, $line['taken']);
        $this->assertNull($line['remaining']);
    }

    /** @test */
    public function an_absence_straddling_two_periods_is_split_between_them()
    {
        $user = User::factory()->create();
        $type = $this->paidLeave();

        // Wednesday 27 May to Wednesday 3 June 2026: 3 working days in the
        // period ending on 31 May, 3 in the one starting on 1 June.
        TimesAbsence::create([
            'user_id' => $user->id,
            'leave_type_id' => $type->id,
            'absence_type' => LeaveBalanceService::DURATION_FULL_DAY,
            'absence_type_day' => LeaveBalanceService::DAY_WORKED,
            'statu' => LeaveBalanceService::STATUS_APPROVED,
            'start_date' => '2026-05-27',
            'end_date' => '2026-06-03',
        ]);

        $before = $this->service->summaryFor($user, Carbon::parse('2026-04-01'));
        $after = $this->service->summaryFor($user, Carbon::parse('2026-07-01'));

        $this->assertEquals(3.0, $before['lines'][0]['taken']);
        $this->assertEquals(3.0, $after['lines'][0]['taken']);
    }
}
