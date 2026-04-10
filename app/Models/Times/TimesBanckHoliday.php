<?php

namespace App\Models\Times;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TimesBanckHoliday extends Model
{
    use HasFactory;

    // Fillable attributes for mass assignment
    protected $fillable= ['fixed',  'date',  'label'];

    /** In-memory cache: ['fixed' => ['mm-dd' => true], 'variable' => ['Y-m-d' => true]] */
    private static ?array $holidayCache = null;

    /**
     * Clear the in-memory cache (call after seeding or mutating the holidays table).
     */
    public static function clearCache(): void
    {
        self::$holidayCache = null;
    }

    /**
     * Load all holidays once into a static cache.
     * Eliminates N+1 SQL queries in WorkingTime loops.
     */
    private static function loadCache(): void
    {
        if (self::$holidayCache !== null) {
            return;
        }

        self::$holidayCache = ['fixed' => [], 'variable' => []];

        foreach (self::all() as $holiday) {
            $d = Carbon::parse($holiday->date);
            if ($holiday->fixed) {
                self::$holidayCache['fixed'][$d->format('m-d')] = true;
            } else {
                self::$holidayCache['variable'][$d->toDateString()] = true;
            }
        }
    }

    /**
     * Checks if a date is a bank holiday.
     * Uses a static in-memory cache — safe to call in tight loops.
     *
     * @param Carbon $date
     * @return bool
     */
    public static function isBankHoliday(Carbon $date): bool
    {
        self::loadCache();

        if (isset(self::$holidayCache['fixed'][$date->format('m-d')])) {
            return true;
        }

        return isset(self::$holidayCache['variable'][$date->toDateString()]);
    }
}
