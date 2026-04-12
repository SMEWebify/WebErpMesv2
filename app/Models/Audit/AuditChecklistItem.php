<?php

namespace App\Models\Audit;

use Illuminate\Database\Eloquent\Model;

class AuditChecklistItem extends Model
{
    protected $table = 'audit_checklist_items';

    protected $fillable = [
        'audit_checklist_id',
        'question',
        'iso_clause',
        'order_index',
    ];

    protected $casts = [
        'order_index' => 'integer',
    ];

    public function checklist()
    {
        return $this->belongsTo(AuditChecklist::class, 'audit_checklist_id');
    }

    public function findings()
    {
        return $this->hasMany(AuditFinding::class, 'audit_checklist_item_id');
    }
}
