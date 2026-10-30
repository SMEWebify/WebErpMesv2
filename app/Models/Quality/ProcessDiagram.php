<?php

namespace App\Models\Quality;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProcessDiagram extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'description',
        'nodes',
        'edges',
        'created_by',
    ];

    protected $casts = [
        'nodes' => 'array',
        'edges' => 'array',
    ];

    /**
     * Journal des modifications (ISO 19443). `nodes` et `edges` sont exclus : chaque
     * enregistrement du schéma recopierait tout le graphe JSON dans activity_log.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'description', 'created_by'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
