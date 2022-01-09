<?php
namespace App\Traits;

use Carbon\Carbon;

trait DeadlineTrait
{
    public function overDeadline():bool
    {
        if ($this->isClosed()) {
            return false;
        }

        return $this->deadline < Carbon::now();
    }

    public function closeToDeadline(Int $days = 2):bool
    {
        return $this->deadline < Carbon::now()->addDays($days);
    }

    public function getDaysUntilDeadlineAttribute(): Int
    {
        return Carbon::now()->startOfDay()->diffInDays($this->deadline, false);
    }
}
