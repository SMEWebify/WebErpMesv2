<?php

namespace App\Models\Audit;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditSchedule extends Model
{
    protected $table = 'audit_schedules';

    protected $fillable = [
        'audit_plan_id',
        'audit_process_id',
        'audit_checklist_id',
        'planned_date',
        'planned_duration_hours',
        'lead_auditor_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'planned_date'           => 'date',
        'planned_duration_hours' => 'integer',
    ];

    public function plan()
    {
        return $this->belongsTo(AuditPlan::class, 'audit_plan_id');
    }

    public function process()
    {
        return $this->belongsTo(AuditProcess::class, 'audit_process_id');
    }

    public function checklist()
    {
        return $this->belongsTo(AuditChecklist::class, 'audit_checklist_id');
    }

    public function leadAuditor()
    {
        return $this->belongsTo(User::class, 'lead_auditor_id');
    }

    public function executions()
    {
        return $this->hasMany(AuditExecution::class, 'audit_schedule_id');
    }

    public function latestExecution()
    {
        return $this->hasOne(AuditExecution::class, 'audit_schedule_id')->latestOfMany();
    }
}
