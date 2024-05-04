<?php

namespace App\Models\Methods;


use App\Models\Planning\SubAssembly;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Methods\MethodsStandardTask;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MethodsStandardNomenclature extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'label',
        'comment'
    ];

    public function tasks()
    {
        return $this->hasMany(MethodsStandardTask::class, 'methods_nomenclature_standard_id');
    }

    public function getAllTaskCountAttribute()
    {        
        $taskCount =  $this->tasks()->count();
        return $taskCount;
    }

    public function TechnicalCut()
    {
        return $this->hasMany(MethodsStandardTask::class, 'methods_nomenclature_standard_id')
                    ->where(function (Builder $query) {
                        return $query->where('type', 1)
                                    ->orWhere('type', 7);
                    })
                    ->orderBy('ordre');
    }

    public function getTechnicalCutTotalSettingTimeAttribute()
    {
        return $this->TechnicalCut->reduce(function ($totalSettingTime, $TechnicalCut) {
        return $totalSettingTime + $TechnicalCut->seting_time;
        },0);
    }

    public function getTechnicalCutTotalUnitTimeAttribute()
    {
        return $this->TechnicalCut->reduce(function ($TotalUnitTime, $TechnicalCut) {
        return $TotalUnitTime + $TechnicalCut->unit_time;
        },0);
    }

    public function getTechnicalCutTotalUnitPricettribute()
    {
        return $this->TechnicalCut->reduce(function ($totalUnitPrice, $TechnicalCut) {
        return $totalUnitPrice + $TechnicalCut->unit_price;
        },0);
    }

    public function getTechnicalCutTotalUnitCostAttribute()
    {
        return $this->TechnicalCut->reduce(function ($totalUnitCost, $TechnicalCut) {
        return $totalUnitCost + $TechnicalCut->unit_cost;
        },0);
    }

    public function getTechnicalCutTMarginAttribute()
    {
        if($this->getTechnicalCutTotalUnitPricettribute() <= 0 ){
            return 0;
        }
        return round((($this->getTechnicalCutTotalUnitPricettribute()/$this->getTechnicalCutTotalUnitCostAttribute())-1)*100,2);
    }

    public function BOM()
    {
        return $this->hasMany(MethodsStandardTask::class, 'methods_nomenclature_standard_id')
                    ->where(function (Builder $query) {
                        return $query->where('type', 2)
                                    ->orWhere('type','=', 3)
                                    ->orWhere('type','=', 4)
                                    ->orWhere('type','=', 5)
                                    ->orWhere('type','=', 6)
                                    ->orWhere('type','=', 8);
                    })
                    ->orderBy('ordre');
    }

    public function getBOMTotalUnitPricettribute()
    {
        return $this->BOM->reduce(function ($totalUnitPrice, $BOM) {
        return $totalUnitPrice + $BOM->unit_price*$BOM->qty;
        },0);
    }

    public function getBOMTotalUnitCostAttribute()
    {
        return $this->BOM->reduce(function ($totalUnitCost, $BOM) {
        return $totalUnitCost + $BOM->unit_cost*$BOM->qty;
        },0);
    }

    public function getBOMTMarginAttribute()
    {
        if($this->getBOMTotalUnitPricettribute() <= 0 ){
            return 0;
        }
        return round((($this->getBOMTotalUnitPricettribute()/$this->getBOMTotalUnitCostAttribute())-1)*100,2);
    }

}
