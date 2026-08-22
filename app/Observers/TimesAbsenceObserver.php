<?php

namespace App\Observers;

use App\Models\Times\TimesAbsence;
use App\Services\HumanResources\LeaveBalanceService;

/**
 * Keeps times_absences.days_count in sync with the dates of the request, so a
 * leave balance never has to expand every date range at read time.
 */
class TimesAbsenceObserver
{
    public function __construct(private readonly LeaveBalanceService $leaveBalance)
    {
    }

    public function saving(TimesAbsence $absence): void
    {
        if (!$absence->isDirty(['start_date', 'end_date', 'absence_type', 'absence_type_day', 'hours_count'])
            && $absence->exists) {
            return;
        }

        $absence->days_count = $this->leaveBalance->daysForAbsence($absence);
    }
}
