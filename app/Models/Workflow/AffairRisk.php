<?php

namespace App\Models\Workflow;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Risque recensé sur une affaire (opportunité), éventuellement rattaché à une
 * de ses commandes. Écrit seulement par App\Services\Affairs\AffairRiskRegister.
 * Jamais supprimé : le registre est journalisé.
 */
class AffairRisk extends Model
{
    use LogsActivity;

    protected $fillable = [
        'opportunities_id', 'orders_id', 'label', 'description', 'category',
        'probability', 'severity', 'mitigation', 'owner_id', 'due_date', 'status',
    ];

    protected $casts = [
        'probability' => 'integer',
        'severity'    => 'integer',
        'due_date'    => 'date',
        'closed_at'   => 'datetime',
    ];

    public function getCriticalityAttribute(): int
    {
        return (int) $this->probability * (int) $this->severity;
    }

    public function opportunity()
    {
        return $this->belongsTo(Opportunities::class, 'opportunities_id');
    }

    public function order()
    {
        return $this->belongsTo(Orders::class, 'orders_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function closer()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['label', 'description', 'category', 'probability', 'severity', 'mitigation',
                'owner_id', 'due_date', 'status', 'orders_id', 'closed_by', 'closed_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
