<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
            $consumption->total_cost = $consumption->kwh * $consumption->cost_per_kwh;
        });
    }

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }
}

