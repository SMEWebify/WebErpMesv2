<?php

namespace App\Models\Inspection;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InspectionControlPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'inspection_project_id',
        'inspection_plan_id',
        'number',
        'label',
        'category',
        'characteristic_type',
        'gdt_symbol',
        'diameter_zone',
        'material_modifier',
        'datums',
        'count',
        'nominal_value',
        'tol_min',
        'tol_max',
        'unit',
        'frequency_type',
        'frequency_value',
        'plan_page',
        'plan_ref',
        'grid_zone',
        'phase',
        'methods_services_id',
        'instrument_type',
        'is_critical',
        'is_fai',
        'order',
        'box_x',
        'box_y',
        'box_w',
        'box_h',
        'bubble_x',
        'bubble_y',
    ];

    protected $casts = [
        'is_critical'   => 'boolean',
        'is_fai'        => 'boolean',
        'diameter_zone' => 'boolean',
        'count'         => 'integer',
        'plan_page'     => 'integer',
        'nominal_value' => 'float',
        'tol_min'       => 'float',
        'tol_max'       => 'float',
        'box_x'         => 'float',
        'box_y'         => 'float',
        'box_w'         => 'float',
        'box_h'         => 'float',
        'bubble_x'      => 'float',
        'bubble_y'      => 'float',
    ];

    public function Project()
    {
        return $this->belongsTo(InspectionProject::class, 'inspection_project_id');
    }

    public function Plan()
    {
        return $this->belongsTo(InspectionPlan::class, 'inspection_plan_id');
    }

    /**
     * Juge une valeur mesurée. Les tolérances sont des écarts signés autour du
     * nominal (une tolérance géométrique : nominal 0, zone dans tol_max). Sans
     * tolérance complète, rien ne permet de juger : on garde le résultat choisi
     * par l'opérateur, conforme par défaut.
     *
     * @return array{result: string, deviation: ?float}
     */
    public function evaluate(?float $value, ?string $requested = null): array
    {
        $deviation = $value !== null && $this->nominal_value !== null ? $value - $this->nominal_value : null;

        if ($value === null || $this->nominal_value === null || $this->tol_min === null || $this->tol_max === null) {
            return ['result' => in_array($requested, ['ok', 'nok'], true) ? $requested : 'ok', 'deviation' => $deviation];
        }

        // Une marge infime absorbe les arrondis flottants (12 + 0.1 = 12.099999…).
        $epsilon = 1e-9;
        $ok = $value >= $this->nominal_value + $this->tol_min - $epsilon
            && $value <= $this->nominal_value + $this->tol_max + $epsilon;

        return ['result' => $ok ? 'ok' : 'nok', 'deviation' => $deviation];
    }

    /** La valeur est-elle jugée par les tolérances, ou par l'opérateur ? */
    public function hasTolerance(): bool
    {
        return $this->nominal_value !== null && $this->tol_min !== null && $this->tol_max !== null;
    }

    public function Measures()
    {
        return $this->hasMany(InspectionMeasure::class);
    }
}
