<?php

namespace App\Models\Inspection;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InspectionControlPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'inspection_project_id',
        'number',
        'label',
        'category',
        'nominal_value',
        'tol_min',
        'tol_max',
        'unit',
        'frequency_type',
        'frequency_value',
        'plan_page',
        'plan_ref',
        'phase',
        'instrument_type',
        'is_critical',
        'order',
    ];

    protected $casts = [
        'is_critical' => 'boolean',
    ];

    public function Project()
    {
        return $this->belongsTo(InspectionProject::class, 'inspection_project_id');
    }

    public function Measures()
    {
        return $this->hasMany(InspectionMeasure::class);
    }
}
