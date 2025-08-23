<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\Times\TimesBanckHoliday;

class WorkingTime
{
    const WORK_START = 8;
    const WORK_END = 18;

    /**
     * Subtract a number of working hours from a date.
     *
     * The method walks backwards hour by hour using CarbonPeriod and
     * skips hours outside the working range (8h-18h), weekends and
     * bank holidays defined by TimesBanckHoliday::isBankHoliday.
     */
    public static function subtractWorkingHours(Carbon $from, int $hours): Carbon
    {
        $remaining = (int) round($hours * 3600);
        $current = $from->copy();

        $period = CarbonPeriod::create($from, '-1 hour');
        foreach ($period as $step) {
            if ($remaining <= 0) {
                break;
            }

            $hourStart = $step->copy()->subHour();
            $current = $hourStart;

            if (!self::isWorkingHour($hourStart)) {
                continue;
            }

            if ($remaining >= 3600) {
                $remaining -= 3600;
            } else {
                // Partial hour: position within current working hour
                $current->addSeconds(3600 - $remaining);
                $remaining = 0;
            }
        }

        return $current;
    }

    private static function isWorkingHour(Carbon $date): bool
    {
        if ($date->isWeekend()) {
            return false;
        }

        if (TimesBanckHoliday::isBankHoliday($date)) {
            return false;
        }

        $hour = $date->hour;
        return $hour >= self::WORK_START && $hour < self::WORK_END;
    }
}
