<?php

namespace App\Models\Maintenance;

use App\Models\Assets\Asset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenancePlan extends Model
{
    protected $table = 'maintenance_plans';

    protected $fillable = [
        'asset_id',
        'title',
        'description',
        'trigger_type',
        'trigger_value',
        'fixed_date',
        'estimated_duration_minutes',
        'required_skill',
        'actions',
        'required_parts',
    ];

    protected $casts = [
        'fixed_date' => 'date',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
