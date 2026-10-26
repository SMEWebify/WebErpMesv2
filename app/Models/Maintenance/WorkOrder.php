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
        'maintenance_plan_id',
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

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MaintenancePlan::class, 'maintenance_plan_id');
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

    /** Un ordre de ces statuts est terminé : le plan peut en préparer un suivant. */
    public const CLOSED_STATUSES = ['completed', 'closed'];

    public const STATUSES = ['draft', 'planned', 'in_progress', 'completed', 'closed'];

    public const PRIORITIES = ['low', 'medium', 'high', 'critical'];

    public const WORK_TYPES = ['preventive', 'corrective', 'improvement', 'safety'];

    public static function statusOptions(): array
    {
        return self::options('status', self::STATUSES);
    }

    public static function priorityOptions(): array
    {
        return self::options('priority', self::PRIORITIES);
    }

    public static function workTypeOptions(): array
    {
        return self::options('work_type', self::WORK_TYPES);
    }

    /** Libellé traduit d'une valeur stockée (status, priority, work_type). */
    public static function label(string $field, ?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $key = "general_content.gmao_{$field}_{$value}_trans_key";

        // Valeur inconnue (saisie historique) : affichée telle quelle plutôt que la clé.
        return __($key) === $key ? ucfirst(str_replace('_', ' ', $value)) : __($key);
    }

    private static function options(string $field, array $values): array
    {
        return collect($values)->mapWithKeys(fn ($v) => [$v => self::label($field, $v)])->all();
    }
}
