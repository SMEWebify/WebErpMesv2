<?php

namespace App\Models\Quality;

use App\Models\User;
use App\Models\Methods\MethodsServices;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QualityControlDevice extends Model
{
    use HasFactory;

    // Fillable attributes for mass assignment
    public const CALIBRATION_ALERT_THRESHOLD_DAYS = 7;

    protected $fillable = [
        'code',
        'label',
        'service_id',
        'user_id',
        'serial_number',
        'picture',
        'calibrated_at',
        'calibration_due_at',
        'calibration_status',
        'calibration_provider',
        'location',
        'capability_index',
    ];

    protected $casts = [
        'calibrated_at' => 'datetime',
        'calibration_due_at' => 'datetime',
        'capability_index' => 'decimal:3',
    ];

    public function UserManagement()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function service()
    {
        return $this->belongsTo(MethodsServices::class, 'service_id');
    }

    /**
     * Get the formatted creation date of the line.
     *
     * This accessor method returns the creation date of line
     * formatted as 'day month year' (e.g., '01 January 2023').
     *
     * @return string The formatted creation date.
     */
    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }

    public function scopeDueForCalibration(Builder $query): Builder
    {
        return $query->whereNotNull('calibration_due_at');
    }

    public function scopeCalibrationDueSoon(Builder $query): Builder
    {
        $today = Carbon::today();

        return $query->dueForCalibration()
            ->whereBetween('calibration_due_at', [$today, $today->copy()->addDays(self::CALIBRATION_ALERT_THRESHOLD_DAYS)]);
    }

    public function scopeCalibrationOverdue(Builder $query): Builder
    {
        $today = Carbon::today();

        return $query->dueForCalibration()
            ->where('calibration_due_at', '<', $today);
    }

    public function getCalibrationAlertLevelAttribute(): ?string
    {
        if (! $this->calibration_due_at) {
            return null;
        }

        $today = Carbon::today();

        if ($this->calibration_due_at->lt($today)) {
            return 'overdue';
        }

        if ($this->calibration_due_at->between($today, $today->copy()->addDays(self::CALIBRATION_ALERT_THRESHOLD_DAYS), true)) {
            return 'due_soon';
        }

        return null;
    }

    public function getCalibrationAlertClassAttribute(): ?string
    {
        return match ($this->calibration_alert_level) {
            'overdue' => 'danger',
            'due_soon' => 'warning',
            default => null,
        };
    }
}
