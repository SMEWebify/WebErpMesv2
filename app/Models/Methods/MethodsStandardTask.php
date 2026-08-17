<?php

namespace App\Models\Methods;

use App\Models\Products\Products;
use App\Models\Methods\MethodsTools;
use App\Models\Methods\MethodsUnits;
use App\Models\Methods\MethodsServices;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MethodsStandardTask extends Model
{
    use HasFactory;

    // Fillable attributes for mass assignment
    protected $fillable= ['label', 
                            'ordre',
                            'methods_nomenclature_standard_id',
                            'sub_assembly_id',
                            'methods_services_id',  
                            'component_id',
                            'seting_time', 
                            'unit_time', 
                            'remaining_time', 
                            'type',
                            'qty',
                            'qty_init',
                            'unit_cost',
                            'unit_price',
                            'methods_units_id',
                            'x_size', 
                            'y_size', 
                            'z_size', 
                            'x_oversize',
                            'y_oversize',
                            'z_oversize',
                            'diameter',
                            'diameter_oversize',
                            'to_schedule',
                            'not_recalculate',
                            'material', 
                            'thickness', 
                            'weight', 
                            'methods_tools_id'];

    public function nomenclature()
    {
        return $this->belongsTo(MethodsStandardTask::class, 'methods_nomenclature_standard_id');
    }

    public function service()
    {
     return $this->belongsTo(MethodsServices::class, 'methods_services_id');
    }

    // NOTE : la relation resources() a été retirée — elle réutilisait le pivot
    // task_resources via une colonne methods_standard_task_id qui n'a jamais
    // existé (toute lecture partait en erreur SQL). Les ressources préférentielles
    // d'une tâche standard demanderont leur propre pivot le jour où le besoin
    // se présentera.
    public function Component()
    {
        return $this->belongsTo(Products::class, 'component_id');
    }

    public function Unit()
    {
        return $this->belongsTo(MethodsUnits::class, 'methods_units_id');
    }

    public function MethodsTools()
    {
        return $this->belongsTo(MethodsTools::class, 'methods_tools_id');
    }

    public function ProductTime()
    {
        return null;
    }

    public function Margin()
    {
        return null;
    }

    public function TotalTime()
    {
        return null;
    }

    public function progress()
    {
        return  null;
    }
    public function getFormattedEndDateAttribute()
    {
        if(!is_null($this->end_date)){
            return date('Y-m-d', strtotime($this->end_date));
        }
        return "NULL";
    }

    /**
     * Get the formatted creation date of the line.
     *
     * This accessor method returns the creation date of line
     * formatted as 'day month year' (e.g., '01 January 2023').
     *
     * @return string The formatted creation date.
     */
    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
}
