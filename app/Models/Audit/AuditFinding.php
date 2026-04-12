<?php

namespace App\Models\Audit;

use App\Models\User;
use App\Models\Quality\QualityAction;
use Illuminate\Database\Eloquent\Model;

class AuditFinding extends Model
{
    protected $table = 'audit_findings';

    protected $fillable = [
        'audit_execution_id',
        'audit_checklist_item_id',
        'type',
        'description',
        'evidence',
        'iso_clause',
        'quality_action_id',
        'created_by',
    ];

    public function execution()
    {
        return $this->belongsTo(AuditExecution::class, 'audit_execution_id');
    }

    public function checklistItem()
    {
        return $this->belongsTo(AuditChecklistItem::class, 'audit_checklist_item_id');
    }

    public function qualityAction()
    {
        return $this->belongsTo(QualityAction::class, 'quality_action_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
