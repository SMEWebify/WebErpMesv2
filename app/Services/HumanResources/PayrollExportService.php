<?php

namespace App\Services\HumanResources;

use App\Models\HumanResources\LeaveType;
use App\Models\Times\TimesAbsence;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Payroll variable elements for a given month.
 *
 * There is no universal format: the DSN is produced by the payroll software,
 * not by the ERP, and every payroll editor (Silae, Sage, Cegid, Quadra...)
 * imports its own layout. What they all need is the same handful of columns —
 * matricule, period, pay item code, quantity — so this produces that neutral
 * shape, one line per employee and per item. Adapting to an editor is then a
 * mapping of the `code` column, not a rewrite.
 */
class PayrollExportService
{
    /** Pay item carrying the hours actually badged. */
    public const CODE_WORKED_HOURS = 'HTRAV';

    /** Pay item carrying the hours booked on production tasks. */
    public const CODE_PRODUCTION_HOURS = 'HPROD';

    public function __construct(
        private readonly LeaveBalanceService $leaveBalance,
        private readonly AttendanceAggregator $attendance,
    ) {
    }

    /**
     * Month containing the given date, as [start, end].
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function periodFor(?string $month = null): array
    {
        $reference = $this->parseMonth($month);

        return [$reference->copy()->startOfMonth(), $reference->copy()->endOfMonth()];
    }

    /**
     * One row per employee and per pay item.
     *
     * Employees with nothing to declare are skipped: a payroll import chokes
     * less on a short file than on hundreds of zero lines.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(?string $month = null, ?int $userId = null): Collection
    {
        [$start, $end] = $this->periodFor($month);

        $users = User::query()
            ->when($userId, fn ($query) => $query->whereKey($userId))
            ->orderBy('name')
            ->get(['id', 'name', 'payroll_number', 'job_title']);

        $leaveTypes = LeaveType::query()->orderBy('ordre')->orderBy('label')->get()->keyBy('id');

        $absences = $this->absenceDaysByUserAndType($users->pluck('id')->all(), $start, $end);

        $badged = $this->attendance->fromPunches($userId, $start->toDateString(), $end->toDateString());
        $produced = $this->attendance->fromTaskActivities($userId, $start->toDateString(), $end->toDateString());

        $rows = collect();

        foreach ($users as $user) {
            $context = [
                'matricule' => $this->matricule($user),
                'name' => $user->name,
                'job_title' => $user->job_title,
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
            ];

            foreach ($absences[$user->id] ?? [] as $leaveTypeId => $days) {
                $type = $leaveTypes->get($leaveTypeId);

                if ($type === null || $days <= 0) {
                    continue;
                }

                $rows->push($context + [
                    'code' => $type->code,
                    'label' => $type->label,
                    'quantity' => round($days, 2),
                    'unit' => 'J',
                ]);
            }

            $badgedHours = round(($badged[$user->id]['total_seconds'] ?? 0) / 3600, 2);

            if ($badgedHours > 0) {
                $rows->push($context + [
                    'code' => self::CODE_WORKED_HOURS,
                    'label' => __('general_content.payroll_worked_hours_trans_key'),
                    'quantity' => $badgedHours,
                    'unit' => 'H',
                ]);
            }

            $producedHours = round(($produced[$user->id]['total_seconds'] ?? 0) / 3600, 2);

            if ($producedHours > 0) {
                $rows->push($context + [
                    'code' => self::CODE_PRODUCTION_HOURS,
                    'label' => __('general_content.payroll_production_hours_trans_key'),
                    'quantity' => $producedHours,
                    'unit' => 'H',
                ]);
            }
        }

        return $rows;
    }

    /**
     * Anomalies worth checking before sending the file to payroll: a forgotten
     * punch-out, or an employee with no matricule.
     *
     * @return array<int, array<string, mixed>>
     */
    public function warnings(?string $month = null, ?int $userId = null): array
    {
        [$start, $end] = $this->periodFor($month);

        $warnings = [];

        foreach ($this->attendance->fromPunches($userId, $start->toDateString(), $end->toDateString()) as $id => $line) {
            if (($line['anomalies'] ?? 0) > 0) {
                $warnings[] = [
                    'user' => $line['user'],
                    'type' => 'attendance_anomaly',
                    'count' => $line['anomalies'],
                ];
            }
        }

        $missing = User::query()
            ->when($userId, fn ($query) => $query->whereKey($userId))
            ->where(fn ($query) => $query->whereNull('payroll_number')->orWhere('payroll_number', ''))
            ->orderBy('name')
            ->get(['id', 'name']);

        foreach ($missing as $user) {
            $warnings[] = [
                'user' => $user,
                'type' => 'missing_payroll_number',
                'count' => 1,
            ];
        }

        return $warnings;
    }

    /**
     * Suggested file name, e.g. paie-2026-08.csv.
     */
    public function fileName(?string $month, string $extension): string
    {
        return 'paie-' . $this->parseMonth($month)->format('Y-m') . '.' . $extension;
    }

    /**
     * Approved absence days per employee and per leave type over the month.
     *
     * Only approved requests are exported: a pending one is not a payroll fact.
     *
     * @param  array<int, int>  $userIds
     * @return array<int, array<int, float>>
     */
    private function absenceDaysByUserAndType(array $userIds, Carbon $start, Carbon $end): array
    {
        $absences = TimesAbsence::query()
            ->whereIn('user_id', $userIds)
            ->whereNotNull('leave_type_id')
            ->where('statu', LeaveBalanceService::STATUS_APPROVED)
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->get();

        $days = [];

        foreach ($absences as $absence) {
            // Clamped to the month: an absence spanning the month boundary is
            // split across two payslips, not charged twice.
            $cost = $this->leaveBalance->daysForAbsence($absence, $start, $end);

            if ($cost <= 0) {
                continue;
            }

            $userId = (int) $absence->user_id;
            $typeId = (int) $absence->leave_type_id;

            $days[$userId][$typeId] = ($days[$userId][$typeId] ?? 0) + $cost;
        }

        return $days;
    }

    private function matricule(User $user): string
    {
        $number = trim((string) $user->payroll_number);

        return $number !== '' ? $number : (string) $user->id;
    }

    private function parseMonth(?string $month): Carbon
    {
        if (is_string($month) && $month !== '') {
            try {
                return Carbon::parse($month);
            } catch (\Throwable) {
                // Fall through to the current month.
            }
        }

        return Carbon::now();
    }
}
