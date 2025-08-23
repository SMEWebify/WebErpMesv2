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
        static::saving(function ($model) {
            $model->total_cost = $model->kwh * $model->cost_per_kwh;
        });
    }

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }
}

