<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\Accounting\LockedPeriod;

class AccountingPeriodService
{
    public function isLocked(Carbon $date): bool
    {
        return LockedPeriod::where('year', $date->year)
            ->where('month', $date->month)
            ->exists();
    }

    public function lock(int $year, int $month): LockedPeriod
    {
        return LockedPeriod::firstOrCreate(
            ['year' => $year, 'month' => $month],
            ['locked_by' => Auth::id(), 'locked_at' => now()]
        );
    }

    public function unlock(int $year, int $month): void
    {
        LockedPeriod::where('year', $year)->where('month', $month)->delete();
    }

    public function all(): \Illuminate\Database\Eloquent\Collection
    {
        return LockedPeriod::with('lockedBy:id,name')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();
    }
}
