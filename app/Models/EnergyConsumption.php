<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Machine;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnergyConsumption extends Model
{
    use HasFactory;

    protected $fillable = [
        'machine_id',
        'kwh',
        'cost_per_kwh',
        'total_cost',
    ];

    protected static function booted()
    {
        static::saving(function (EnergyConsumption $consumption) {
            if ($consumption->kwh !== null && $consumption->cost_per_kwh !== null) {
                $consumption->total_cost = $consumption->kwh * $consumption->cost_per_kwh;
            }
        });
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function getTotalCostAttribute($value)
    {
        if ($value !== null) {
            return $value;
        }

        if ($this->kwh !== null && $this->cost_per_kwh !== null) {
            return $this->kwh * $this->cost_per_kwh;
        }

        return null;

    }
}

