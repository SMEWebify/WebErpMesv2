<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EnergyConsumption extends Model
{
    use HasFactory;

    protected $fillable = [
        'kwh_consumed',
        'cost',
    ];

    protected static function booted()
    {
        static::saving(function (EnergyConsumption $consumption) {
            $consumption->cost = $consumption->kwh_consumed * config('energy.price_per_kwh');
        });
    }

    public function getCostAttribute($value)
    {
        return $value ?? $this->kwh_consumed * config('energy.price_per_kwh');
    }
}
