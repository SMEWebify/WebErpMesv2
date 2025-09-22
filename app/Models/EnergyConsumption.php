<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Methods\MethodsRessources;
use Illuminate\Database\Eloquent\Model;

class EnergyConsumption extends Model
{
    use HasFactory;

    protected $fillable = [
        'methods_ressource_id',
        'kwh',
        'cost_per_kwh',
        'total_cost',
    ];

    public function methodsRessource()
    {
        return $this->belongsTo(MethodsRessources::class, 'methods_ressource_id');
    }

    protected static function booted()
    {
        static::saving(function (EnergyConsumption $consumption) {
            if ($consumption->kwh !== null && $consumption->cost_per_kwh !== null) {
                $consumption->total_cost = $consumption->kwh * $consumption->cost_per_kwh;
            }
        });
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

