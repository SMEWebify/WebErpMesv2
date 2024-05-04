<?php

namespace App\Models\Methods;

use App\Models\Products\Products;
use App\Models\Methods\MethodsTools;
use App\Models\Methods\MethodsUnits;
use App\Models\Methods\MethodsServices;
use Illuminate\Database\Eloquent\Model;
use App\Models\Methods\MethodsRessources;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MethodsStandardTask extends Model
{
    use HasFactory;

    protected $fillable = ['label', 
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

    public function resources() {
        return $this->belongsToMany(MethodsRessources::class, 'task_resources')
        ->withPivot(['autoselected_ressource', 'userforced_ressource'])
        ->withTimestamps();
    }
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

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
}
