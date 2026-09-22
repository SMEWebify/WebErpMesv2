<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\Times\TimesBanckHoliday;
use App\Models\Times\WorkShiftPattern;

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

            if (!self::isWorkingInstant($hourStart)) {
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

    /**
     * Add a number of working hours to a date. Miroir de subtractWorkingHours
     * pour le forward-scheduling (ASAP) : on avance heure par heure et on saute
     * les créneaux non travaillés (nuits, week-ends, fériés).
     *
     * L'ancre est d'abord recalée sur le prochain instant travaillé — sinon
     * un ancrage à 18h vendredi consommerait la première heure sur samedi 0h.
     */
    public static function addWorkingHours(Carbon $from, int|float $hours): Carbon
    {
        $remaining = (int) round(((float) $hours) * 3600);
        $current = $from->copy();

        if ($remaining <= 0) {
            return $current;
        }

        // Recale l'ancre sur un instant travaillé : sans ça, un ancrage à
        // 18h (fin de journée) commencerait à consommer les heures de nuit.
        while (!self::isWorkingInstant($current)) {
            $current->addHour()->startOfHour();
        }

        $period = CarbonPeriod::create($current, '1 hour');
        foreach ($period as $step) {
            if ($remaining <= 0) {
                break;
            }

            $hourStart = $step->copy();

            if (!self::isWorkingInstant($hourStart)) {
                $current = $hourStart->copy()->addHour();
                continue;
            }

            if ($remaining >= 3600) {
                $remaining -= 3600;
                $current = $hourStart->copy()->addHour();
            } else {
                // Fraction d'heure : on s'arrête pile dedans.
                $current = $hourStart->copy()->addSeconds($remaining);
                $remaining = 0;
            }
        }

        return $current;
    }

    /** L'instant tombe-t-il dans une plage travaillée ? */
    public static function isWorkingInstant(Carbon $date): bool
    {
        if (TimesBanckHoliday::isBankHoliday($date)) {
            return false;
        }

        // Régime horaire de l'atelier s'il en existe un : il porte les équipes
        // (1×8, 2×8, 3×8), donc les nuits et les samedis travaillés.
        $pattern = WorkShiftPattern::defaultPattern();

        if ($pattern) {
            return $pattern->coversInstant($date);
        }

        // Horaires historiques : journée continue, du lundi au vendredi.
        if ($date->isWeekend()) {
            return false;
        }

        $hour = $date->hour;
        return $hour >= self::WORK_START && $hour < self::WORK_END;
    }
}
