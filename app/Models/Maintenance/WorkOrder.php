<?php

namespace App\Models\Maintenance;

use App\Models\Assets\Asset;
use App\Models\Times\TimesMachineEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrder extends Model
{
    protected $table = 'maintenance_work_orders';

    protected $fillable = [
        'asset_id',
        'times_machine_event_id',
        'title',
        'description',
        'actions_performed',
        'parts_consumed',
        'comments',
        'priority',
        'work_type',
        'status',
        'requested_at',
        'scheduled_at',
        'started_at',
        'finished_at',
        'completed_at',
        'estimated_duration_minutes',
        'actual_duration_minutes',
        'assigned_to',
        'failure_type',
        'severity',
        'machine_stopped',
        'failure_started_at',
        'created_by',
    ];

    protected $casts = [
        'requested_at' => 'date',
        'scheduled_at' => 'date',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'completed_at' => 'date',
        'failure_started_at' => 'datetime',
        'machine_stopped' => 'boolean',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function machineEvent(): BelongsTo
    {
        return $this->belongsTo(TimesMachineEvent::class, 'times_machine_event_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
