<?php

namespace App\Models\Inspection;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InspectionInstrument extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'serial',
        'calibration_due_at',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'calibration_due_at' => 'date',
    ];

    public function Measures()
    {
        return $this->hasMany(InspectionMeasure::class, 'instrument_id');
    }
}
