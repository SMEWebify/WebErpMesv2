<?php

namespace App\Models\Assets;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Accounting\AccountingEntry;
use App\Models\Maintenance\MaintenancePlan;
use App\Models\Maintenance\WorkOrder;
use App\Models\Methods\MethodsRessources;

class Asset extends Model
{
    protected $fillable = [
        'name',
        'category',
        'methods_ressource_id',
        'acquisition_value',
        'acquisition_date',
        'depreciation_duration',
    ];

    protected $casts = [
        'acquisition_date' => 'date',
        'acquisition_value' => 'decimal:2',
        'depreciation_duration' => 'integer',
    ];

    public function accountingEntries(): HasMany
    {
        return $this->hasMany(AccountingEntry::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function maintenancePlans(): HasMany
    {
        return $this->hasMany(MaintenancePlan::class);
    }

    public function methodsRessource(): BelongsTo
    {
        return $this->belongsTo(MethodsRessources::class, 'methods_ressource_id');
    }
}
