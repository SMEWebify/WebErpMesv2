<?php

namespace App\Services\HumanResources;

use App\Models\Admin\UserEmploymentContracts;
use App\Models\HumanResources\LeaveBalance;
use App\Models\HumanResources\LeaveType;
use App\Models\Times\TimesAbsence;
use App\Models\Times\TimesBanckHoliday;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Turns absence requests into a leave balance.
 *
 * The credit side (entitlement, carry over, adjustment) is stored on
 * leave_balances; the debit side is always recomputed from times_absences so
 * the two cannot drift. Each absence carries its resolved cost in days_count,
 * written on save by TimesAbsenceObserver, so a summary never has to expand
 * every date range.
 */
class LeaveBalanceService
{
    /** Absence pending a manager decision. */
    public const STATUS_PENDING = 1;

    /** Absence approved: it is what actually consumes the balance. */
    public const STATUS_APPROVED = 2;

    /** Absence refused: ignored everywhere. */
    public const STATUS_REFUSED = 3;

    /** times_absences.absence_type - how the duration is expressed. */
    public const DURATION_FULL_DAY = 1;
    public const DURATION_ONE_HALF_DAY = 2;
    public const DURATION_TWO_HALF_DAYS = 3;
    public const DURATION_HOURS = 4;

    /** times_absences.absence_type_day - which days are countable. */
    public const DAY_CALENDAR = 1;
    public const DAY_WORKABLE = 2;   // Monday to Saturday
    public const DAY_WORKED = 3;     // Monday to Friday

    /**
     * Reference period containing the given date, as [start, end].
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function periodFor(?Carbon $reference = null): array
    {
        $reference = ($reference ?? Carbon::now())->copy()->startOfDay();

        $month = (int) config('hr.leave_period_start_month', 6);
        $day = (int) config('hr.leave_period_start_day', 1);

        $start = Carbon::create($reference->year, $month, $day)->startOfDay();

        if ($reference->lt($start)) {
            $start->subYear();
        }

        return [$start, $start->copy()->addYear()->subDay()->endOfDay()];
    }

    /**
     * Human label of a period, e.g. "2026 / 2027" - or a single year when the
     * period is aligned on the calendar year.
     */
    public function periodLabel(Carbon $start, Carbon $end): string
    {
        return $start->year === $end->year
            ? (string) $start->year
            : $start->year . ' / ' . $end->year;
    }

    /**
     * Cost in days of an absence, weekends and bank holidays already removed.
     *
     * When a window is given, only the part of the absence falling inside it is
     * counted, so an absence straddling two reference periods is split rather
     * than charged twice.
     */
    public function daysForAbsence(TimesAbsence $absence, ?Carbon $windowStart = null, ?Carbon $windowEnd = null): float
    {
        $start = $this->toDate($absence->start_date);
        $end = $this->toDate($absence->end_date);

        if ($start === null || $end === null || $end->lt($start)) {
            return 0.0;
        }

        if ($windowStart !== null && $start->lt($windowStart)) {
            $start = $windowStart->copy()->startOfDay();
        }

        if ($windowEnd !== null && $end->gt($windowEnd)) {
            $end = $windowEnd->copy()->startOfDay();
        }

        if ($end->lt($start)) {
            return 0.0;
        }

        $countableDays = $this->countableDays($start, $end, (int) $absence->absence_type_day);

        if ($countableDays === 0) {
            return 0.0;
        }

        return match ((int) $absence->absence_type) {
            self::DURATION_ONE_HALF_DAY => 0.5,
            self::DURATION_TWO_HALF_DAYS => 1.0,
            self::DURATION_HOURS => $this->hoursToDays((float) ($absence->hours_count ?? 0), (int) $absence->user_id),
            default => (float) $countableDays,
        };
    }

    /**
     * Number of countable days in a range for a given day convention.
     */
    public function countableDays(Carbon $start, Carbon $end, int $dayType): int
    {
        $holidays = $this->bankHolidays();
        $count = 0;

        for ($date = $start->copy()->startOfDay(); $date->lte($end); $date->addDay()) {
            if ($this->isCountable($date, $dayType, $holidays)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Balance summary of one employee for the period containing $reference.
     *
     * @return array{period_start: Carbon, period_end: Carbon, period_label: string, lines: array<int, array<string, mixed>>}
     */
    public function summaryFor(User|int $user, ?Carbon $reference = null): array
    {
        $userId = $user instanceof User ? (int) $user->id : (int) $user;

        return $this->summaryForMany([$userId], $reference)[$userId];
    }

    /**
     * Same summary for a whole list of employees, in a fixed number of
     * queries: the company-wide screen would otherwise fire three per row.
     *
     * @param  iterable<int, User|int>  $users
     * @return array<int, array{period_start: Carbon, period_end: Carbon, period_label: string, lines: array<int, array<string, mixed>>}>
     */
    public function summaryForMany(iterable $users, ?Carbon $reference = null): array
    {
        $userIds = [];

        foreach ($users as $user) {
            $userIds[] = $user instanceof User ? (int) $user->id : (int) $user;
        }

        $userIds = array_values(array_unique($userIds));

        [$periodStart, $periodEnd] = $this->periodFor($reference);

        $types = LeaveType::query()->orderBy('ordre')->orderBy('label')->get();

        $balancesByUser = LeaveBalance::query()
            ->whereIn('user_id', $userIds)
            ->whereDate('period_start', $periodStart->toDateString())
            ->get()
            ->groupBy('user_id');

        $absencesByUser = TimesAbsence::query()
            ->whereIn('user_id', $userIds)
            ->whereNotNull('leave_type_id')
            ->whereDate('start_date', '<=', $periodEnd->toDateString())
            ->whereDate('end_date', '>=', $periodStart->toDateString())
            ->whereIn('statu', [self::STATUS_PENDING, self::STATUS_APPROVED])
            ->get()
            ->groupBy('user_id');

        $summaries = [];

        foreach ($userIds as $userId) {
            $summaries[$userId] = $this->buildSummary(
                $types,
                collect($balancesByUser->get($userId, []))->keyBy('leave_type_id'),
                collect($absencesByUser->get($userId, [])),
                $periodStart,
                $periodEnd
            );
        }

        return $summaries;
    }

    /**
     * Assemble one summary from data already loaded.
     *
     * @param  Collection<int, LeaveType>  $types
     * @param  Collection<int, LeaveBalance>  $balances
     * @param  Collection<int, TimesAbsence>  $absences
     * @return array{period_start: Carbon, period_end: Carbon, period_label: string, lines: array<int, array<string, mixed>>}
     */
    private function buildSummary(Collection $types, Collection $balances, Collection $absences, Carbon $periodStart, Carbon $periodEnd): array
    {
        $lines = [];

        foreach ($types as $type) {
            /** @var LeaveBalance|null $balance */
            $balance = $balances->get($type->id);

            $ofType = $absences->where('leave_type_id', $type->id);

            $taken = $this->sumWithinPeriod($ofType->where('statu', self::STATUS_APPROVED), $periodStart, $periodEnd);
            $pending = $this->sumWithinPeriod($ofType->where('statu', self::STATUS_PENDING), $periodStart, $periodEnd);

            $acquired = $balance?->acquired_days ?? 0.0;

            // A type that does not consume a balance (sick leave) is still
            // reported, but only as a count of days taken.
            $lines[] = [
                'type' => $type,
                'balance' => $balance,
                'entitled' => (float) ($balance->entitled_days ?? 0),
                'carried_over' => (float) ($balance->carried_over_days ?? 0),
                'adjustment' => (float) ($balance->adjustment_days ?? 0),
                'acquired' => $acquired,
                'taken' => $taken,
                'pending' => $pending,
                'remaining' => $type->counts_against_balance
                    ? round($acquired - $taken - $pending, 2)
                    : null,
            ];
        }

        return [
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'period_label' => $this->periodLabel($periodStart, $periodEnd),
            'lines' => $lines,
        ];
    }

    /**
     * Days already stored on the absences, clamped to the period.
     *
     * @param  Collection<int, TimesAbsence>  $absences
     */
    private function sumWithinPeriod(Collection $absences, Carbon $periodStart, Carbon $periodEnd): float
    {
        $total = 0.0;

        foreach ($absences as $absence) {
            $start = $this->toDate($absence->start_date);
            $end = $this->toDate($absence->end_date);

            // Fully inside the period: the stored cost is already the answer.
            if ($start !== null && $end !== null
                && $start->gte($periodStart) && $end->lte($periodEnd)) {
                $total += (float) $absence->days_count;
                continue;
            }

            $total += $this->daysForAbsence($absence, $periodStart, $periodEnd);
        }

        return round($total, 2);
    }

    /**
     * Convert an absence expressed in hours into days, using the weekly
     * duration of the employee's active contract when there is one.
     */
    private function hoursToDays(float $hours, int $userId): float
    {
        if ($hours <= 0) {
            return 0.0;
        }

        return round($hours / $this->dailyHoursFor($userId), 2);
    }

    private function dailyHoursFor(int $userId): float
    {
        $fallback = (float) config('hr.default_daily_hours', 7);

        $weekly = (float) (UserEmploymentContracts::query()
            ->where('user_id', $userId)
            ->whereIn('statu', [1, 2])
            ->orderByDesc('start_date')
            ->value('weekly_duration') ?? 0);

        if ($weekly <= 0) {
            return $fallback > 0 ? $fallback : 7.0;
        }

        return round($weekly / 5, 2);
    }

    /**
     * @param  array{dates: array<string, true>, recurring: array<string, true>}  $holidays
     */
    private function isCountable(Carbon $date, int $dayType, array $holidays): bool
    {
        if ($dayType === self::DAY_CALENDAR) {
            return true;
        }

        if ($date->isSunday()) {
            return false;
        }

        if ($dayType === self::DAY_WORKED && $date->isSaturday()) {
            return false;
        }

        return !isset($holidays['dates'][$date->toDateString()])
            && !isset($holidays['recurring'][$date->format('m-d')]);
    }

    /**
     * Bank holidays split between one-off dates and dates recurring every year.
     *
     * @return array{dates: array<string, true>, recurring: array<string, true>}
     */
    private function bankHolidays(): array
    {
        return Cache::remember('hr_bank_holidays', now()->addHour(), function () {
            $holidays = ['dates' => [], 'recurring' => []];

            foreach (TimesBanckHoliday::query()->get(['date', 'fixed']) as $holiday) {
                $date = $this->toDate($holiday->date);

                if ($date === null) {
                    continue;
                }

                if ((int) $holiday->fixed === 1) {
                    $holidays['recurring'][$date->format('m-d')] = true;
                    continue;
                }

                $holidays['dates'][$date->toDateString()] = true;
            }

            return $holidays;
        });
    }

    private function toDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
