<?php

namespace App\Models\Products;

use App\Models\File;
use App\Models\Planning\Task;
use Spatie\Activitylog\LogOptions;
use App\Models\Workflow\QuoteLines;
use App\Models\Methods\MethodsUnits;
use App\Models\Planning\SubAssembly;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsServices;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Products\StockLocationProducts;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Products extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['code',
                            'label', 
                            'ind',
                            'methods_services_id', 
                            'methods_families_id', 
                            'purchased', 
                            'purchased_price', 
                            'sold', 
                            'selling_price', 
                            'methods_units_id', 
                            'material', 
                            'thickness', 
                            'weight', 
                            'x_size', 
                            'y_size', 
                            'z_size', 
                            'x_oversize',
                            'y_oversize',
                            'z_oversize',
                            'comment',
                            'tracability_type',
                            'qty_eco_min',
                            'qty_eco_max',
                            'diameter',
                            'diameter_oversize',
                            'section_size',
                            'picture',
                            'stl_file',];

    public function service()
    {
        return $this->belongsTo(MethodsServices::class, 'methods_services_id');
    }

    public function family()
    {
        return $this->belongsTo(MethodsFamilies::class, 'methods_families_id');
    }

    public function Unit()
    {
        return $this->belongsTo(MethodsUnits::class, 'methods_units_id');
    }

    public function Stock_location_product()
    {
        return $this->hasMany(StockLocationProducts::class, 'stock_location_products');
    }

    public function Task()
    {
        return $this->hasMany(Task::class, 'products_id')->orderBy('ordre');
    }

    public function getTaskCountAttribute()
    {
        $taskCount =  $this->Task()->count();
        $subAssemblyCount = $this->SubAssembly()->count();
        return '('. $taskCount .') ('. $subAssemblyCount .')';
    }


    public function TechnicalCut()
    {
        return $this->hasMany(Task::class, 'products_id')
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
        
        return round((1-($this->getTechnicalCutTotalUnitCostAttribute()/$this->getTechnicalCutTotalUnitPricettribute()))*100,2);
    }

    public function BOM()
    {
        return $this->hasMany(Task::class, 'products_id')
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

        return round((1-($this->getBOMTotalUnitCostAttribute()/$this->getBOMTotalUnitPricettribute()))*100,2);
    }

    public function Quotelines()
    {
        return $this->hasMany(QuoteLines::class);
    }

    public function files()
    {
        return $this->hasMany(File::class);
    }

    public function SubAssembly()
    {
        return $this->hasMany(SubAssembly::class, 'products_id')->orderBy('ordre');
    }

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['code', 'label']);
        // Chain fluent methods for configuration options
    }
}
