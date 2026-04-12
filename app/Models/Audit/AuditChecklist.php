<?php

namespace App\Models\Audit;

use Illuminate\Database\Eloquent\Model;

class AuditChecklist extends Model
{
    protected $table = 'audit_checklists';

    protected $fillable = [
        'title',
        'iso_clause',
        'audit_process_id',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function process()
    {
        return $this->belongsTo(AuditProcess::class, 'audit_process_id');
    }

    public function items()
    {
        return $this->hasMany(AuditChecklistItem::class, 'audit_checklist_id')->orderBy('order_index');
    }

    public function schedules()
    {
        return $this->hasMany(AuditSchedule::class, 'audit_checklist_id');
    }
}
