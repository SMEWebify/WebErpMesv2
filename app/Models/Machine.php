<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Machine extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function energyConsumptions()
    {
        return $this->hasMany(EnergyConsumption::class);
    }
}

