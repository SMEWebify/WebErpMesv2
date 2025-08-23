<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Planning\Task;
use App\Models\Methods\MethodsRessources;
use App\Models\Times\TimesBanckHoliday;
use App\Support\WorkingTime;
use Illuminate\Support\Collection;

class TaskDateCalculator
{
    /**
     * Adjust date to previous working day if it falls on weekend or bank holiday.
     */
    public function adjustForWeekendsAndHolidays(Carbon $date): Carbon
    {
        do {
            if ($date->isSaturday()) {
                $date->subDay();
            } elseif ($date->isSunday()) {
                $date->subDays(2);
            }
            if (TimesBanckHoliday::isBankHoliday($date)) {
                $date->subDay();
            }
        } while ($date->isWeekend() || TimesBanckHoliday::isBankHoliday($date));

        return $date;
    }

    /**
     * Adjust a date by subtracting the given number of seconds while
     * respecting working hours, weekends and bank holidays.
     */
    public function adjustForWorkingHours(Carbon $date, int $secondsToSubtract): Carbon
    {
        $hours = $secondsToSubtract / 3600;
        return WorkingTime::subtractWorkingHours($date, $hours);
    }

    /**
     * Calculate start and end dates for the given task.
     *
     * @return array{0: Carbon,1: Carbon}
     */
    public function calculateTaskDates(Task $task, Carbon $end): array
    {
        $end = $this->adjustForWeekendsAndHolidays($end);
        $duration = ($task->seting_time ?? 0) + (($task->unit_time ?? 0) * ($task->qty ?? 0));
        $start = (clone $end)->subHours($duration);
        $start = $this->adjustForWeekendsAndHolidays($start);
        return [$start, $end];
    }

    /**
     * Select a resource that still has capacity.
     */
    public function selectResourceForTask(Task $task, Collection|array $resources): ?MethodsRessources
    {
        foreach ($resources as $resource) {
            if ($resource->capacity >= 1) {
                return $resource;
            }
        }
        return null;
    }
}
