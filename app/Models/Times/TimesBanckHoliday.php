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

    /**
     * Checks if a date is a holiday.
     *
     * @param Carbon $date
     * @return bool
     */
    public static function isBankHoliday(Carbon $date): bool
    {
        $driver = self::query()->getConnection()->getDriverName();

        return self::where(function ($query) use ($date, $driver) {
                // 1 Check fixed holidays (base only on day + month)
                $query->where('fixed', true)
                    ->when($driver === 'sqlite', function ($sqliteQuery) use ($date) {
                        $sqliteQuery->whereRaw("strftime('%m', date) = ?", [sprintf('%02d', $date->month)])
                            ->whereRaw("strftime('%d', date) = ?", [sprintf('%02d', $date->day)]);
                    }, function ($defaultQuery) use ($date) {
                        $defaultQuery->whereRaw('MONTH(date) = ?', [$date->month])
                            ->whereRaw('DAY(date) = ?', [$date->day]);
                    });

                // Check for non-fixed holidays (take into account the whole year)
                $query->orWhere(function ($subQuery) use ($date) {
                    $subQuery->where('fixed', false)
                            ->whereDate('date', $date->toDateString());
                });
            })
            ->exists();
    }
}
