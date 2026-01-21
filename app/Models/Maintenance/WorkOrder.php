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
        'priority',
        'status',
        'requested_at',
        'scheduled_at',
        'completed_at',
        'created_by',
    ];

    protected $casts = [
        'requested_at' => 'date',
        'scheduled_at' => 'date',
        'completed_at' => 'date',
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
}
