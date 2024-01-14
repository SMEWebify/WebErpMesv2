<?php

namespace App\Models\Workflow;

use App\Models\Planning\Task;
use App\Models\Workflow\Quotes;
use App\Models\Products\Products;
use Spatie\Activitylog\LogOptions;
use App\Models\Methods\MethodsUnits;
use Illuminate\Database\Eloquent\Model;
use App\Models\Accounting\AccountingVat;
use App\Models\Planning\SubAssembly;
use App\Models\Workflow\QuoteLineDetails;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QuoteLines extends Model
{
    use HasFactory, LogsActivity;
    
    protected $fillable = ['quotes_id', 
                            'ordre', 
                            'code',
                            'product_id',
                            'label',
                            'qty',
                            'methods_units_id',
                            'selling_price',
                            'discount',
                            'accounting_vats_id',
                            'delivery_date',
                            'statu'
                        ];

    public function quote()
    {
        return $this->belongsTo(Quotes::class, 'quotes_id');
    }

    public function Product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function Unit()
    {
        return $this->belongsTo(MethodsUnits::class, 'methods_units_id');
    }
    
    public function VAT()
    {
        return $this->belongsTo(AccountingVat::class, 'accounting_vats_id');
    }

    public function Task()
    {
        return $this->hasMany(Task::class, 'quote_lines_id')->orderBy('ordre');
    }

    public function QuoteLineDetails()
    {
        return $this->hasOne(QuoteLineDetails::class, 'quote_lines_id');
    }

    public function getAllTaskCountAttribute()
    {
        $taskCount =  $this->Task()->count();
        $subAssemblyCount = $this->SubAssembly()->count();
        return '('. $taskCount .') ('. $subAssemblyCount .')';
    }

    public function TechnicalCut()
    {
        return $this->hasMany(Task::class, 'quote_lines_id')
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
        return $this->hasMany(Task::class, 'quote_lines_id')
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

    public function SubAssembly()
    {
        return $this->hasMany(SubAssembly::class, 'quote_lines_id')->orderBy('ordre');
    }

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['quotes_id', 'code', 'label', 'statu']);
        // Chain fluent methods for configuration options
    }
}
