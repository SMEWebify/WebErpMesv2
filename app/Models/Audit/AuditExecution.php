<?php

namespace App\Models\Audit;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditExecution extends Model
{
    protected $table = 'audit_executions';

    protected $fillable = [
        'audit_schedule_id',
        'actual_date',
        'actual_duration_hours',
        'summary',
        'conclusion',
        'status',
        'created_by',
    ];

    protected $casts = [
        'actual_date'           => 'date',
        'actual_duration_hours' => 'integer',
    ];

    public function schedule()
    {
        return $this->belongsTo(AuditSchedule::class, 'audit_schedule_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function findings()
    {
        return $this->hasMany(AuditFinding::class, 'audit_execution_id');
    }

    public function getMajorNcCountAttribute(): int
    {
        return $this->findings()->where('type', 'major_nc')->count();
    }

    public function getMinorNcCountAttribute(): int
    {
        return $this->findings()->where('type', 'minor_nc')->count();
    }
}
