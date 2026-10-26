<?php

namespace App\Models\Maintenance;

use App\Models\Assets\Asset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenancePlan extends Model
{
    protected $table = 'maintenance_plans';

    /** Déclencheurs qu'on sait dater : les compteurs (heures, cycles) ne sont relevés nulle part. */
    public const SCHEDULABLE_TRIGGERS = ['time', 'fixed_date'];

    protected $fillable = [
        'asset_id',
        'title',
        'description',
        'trigger_type',
        'trigger_value',
        'fixed_date',
        'auto_generate',
        'interval_days',
        'lead_days',
        'estimated_duration_minutes',
        'required_skill',
        'actions',
        'required_parts',
    ];

    protected $casts = [
        'fixed_date' => 'date',
        'auto_generate' => 'boolean',
        'interval_days' => 'integer',
        'lead_days' => 'integer',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    public static function triggerTypeOptions(): array
    {
        return collect(['time', 'machine_hours', 'cycles', 'fixed_date'])
            ->mapWithKeys(fn ($type) => [$type => self::triggerTypeLabel($type)])
            ->all();
    }

    public static function triggerTypeLabel(?string $type): string
    {
        if (! $type) {
            return '';
        }

        $key = "general_content.gmao_trigger_{$type}_trans_key";

        return __($key) === $key ? ucfirst(str_replace('_', ' ', $type)) : __($key);
    }
}
