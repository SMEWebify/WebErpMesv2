<?php

namespace App\Models\Audit;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuditPlan extends Model
{
    use SoftDeletes;

    protected $table = 'audit_plans';

    protected $fillable = [
        'title',
        'year',
        'scope',
        'status',
        'created_by',
    ];

    protected $casts = [
        'year' => 'integer',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function schedules()
    {
        return $this->hasMany(AuditSchedule::class, 'audit_plan_id');
    }

    public function getCompletionRateAttribute(): float
    {
        $total = $this->schedules()->count();
        if ($total === 0) return 0;
        $completed = $this->schedules()->where('status', 'completed')->count();
        return round(($completed / $total) * 100, 1);
    }
}
