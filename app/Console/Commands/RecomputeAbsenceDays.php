<?php

namespace App\Console\Commands;

use App\Models\Times\TimesAbsence;
use App\Services\HumanResources\LeaveBalanceService;
use Illuminate\Console\Command;

/**
 * Backfills times_absences.days_count.
 *
 * Absences filed before the leave balance existed carry a days_count of 0, and
 * a change in the bank holiday calendar can shift the cost of a request. Both
 * cases are fixed by replaying the calculation.
 */
class RecomputeAbsenceDays extends Command
{
    protected $signature = 'hr:recompute-absence-days {--dry-run : Show what would change without saving}';

    protected $description = 'Recompute the cost in days of every absence request';

    public function handle(LeaveBalanceService $leaveBalance): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $changed = 0;
        $scanned = 0;

        TimesAbsence::query()->orderBy('id')->chunkById(200, function ($absences) use ($leaveBalance, $dryRun, &$changed, &$scanned) {
            foreach ($absences as $absence) {
                $scanned++;

                $days = $leaveBalance->daysForAbsence($absence);

                if (abs((float) $absence->days_count - $days) < 0.001) {
                    continue;
                }

                $this->line(sprintf(
                    '#%d %s → %s : %s → %s j',
                    $absence->id,
                    $absence->start_date,
                    $absence->end_date,
                    (float) $absence->days_count,
                    $days
                ));

                $changed++;

                if (!$dryRun) {
                    $absence->days_count = $days;
                    $absence->saveQuietly();
                }
            }
        });

        $this->info(sprintf(
            '%d absence(s) scanned, %d %s.',
            $scanned,
            $changed,
            $dryRun ? 'would be updated' : 'updated'
        ));

        return self::SUCCESS;
    }
}
