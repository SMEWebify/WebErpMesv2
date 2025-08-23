<?php

namespace App\Models;

use App\Models\Methods\MethodsRessources;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnergyConsumption extends Model
{
    use HasFactory;

    // Fillable attributes for mass assignment
    protected $fillable = [
        'machine_id',
        'kwh_consumed',
        'cost',
        'recorded_at',
    ];

    /**
     * Get the machine or resource associated with this energy consumption record.
     */
    public function machine()
    {
        return $this->belongsTo(MethodsRessources::class, 'machine_id');
    }
}
