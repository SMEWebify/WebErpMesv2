<?php

namespace App\Models\HumanResources;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'leave_type_id',
        'period_start',
        'period_end',
        'entitled_days',
        'carried_over_days',
        'adjustment_days',
        'comment',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'entitled_days' => 'decimal:2',
        'carried_over_days' => 'decimal:2',
        'adjustment_days' => 'decimal:2',
    ];

    /**
     * Entitlement of one employee, for one type, on one reference period.
     *
     * The lookup goes through whereDate: period_start is a date cast, which
     * SQLite stores with a time part, so a raw string comparison would miss
     * the row and let the unique index fire.
     */
    public function scopeForPeriod($query, int $userId, int $leaveTypeId, Carbon $periodStart)
    {
        return $query->where('user_id', $userId)
            ->where('leave_type_id', $leaveTypeId)
            ->whereDate('period_start', $periodStart->toDateString());
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Total credited on the period: entitlement + carry over + manual fix.
     */
    public function getAcquiredDaysAttribute(): float
    {
        return round((float) $this->entitled_days
            + (float) $this->carried_over_days
            + (float) $this->adjustment_days, 2);
    }
}
