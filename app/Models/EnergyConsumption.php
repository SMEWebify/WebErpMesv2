<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Methods\MethodsRessources;
use App\Models\Machine;
use Illuminate\Database\Eloquent\Model;

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
            $consumption->cost = $consumption->kwh_consumed * config('energy.price_per_kwh');

            if (! is_null($consumption->kwh) && ! is_null($consumption->cost_per_kwh)) {
                $consumption->total_cost = $consumption->kwh * $consumption->cost_per_kwh;
            }

            $consumption->total_cost = $consumption->kwh * $consumption->cost_per_kwh;

        });
    }

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }
}

