<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EnergyConsumption extends Model
{
    use HasFactory;

    protected $fillable = [
        'recorded_at',
        'amount',
    ];
}
