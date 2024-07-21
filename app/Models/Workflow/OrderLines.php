<?php

namespace App\Models\Workflow;

use App\Models\Planning\Task;
use App\Models\Workflow\Orders;
use App\Models\Products\Products;
use App\Models\Products\StockMove;
use Spatie\Activitylog\LogOptions;
use App\Models\Methods\MethodsUnits;
use App\Models\Planning\SubAssembly;
use App\Models\Workflow\InvoiceLines;
use App\Models\Products\SerialNumbers;
use App\Models\Workflow\DeliveryLines;
use Illuminate\Database\Eloquent\Model;
use App\Models\Accounting\AccountingVat;
use App\Models\Workflow\OrderLineDetails;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Quality\QualityNonConformity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderLines extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['orders_id', 
                            'ordre', 
                            'code',
                            'product_id',
                            'label',
                            'qty',
                            'delivered_qty',
                            'delivered_remaining_qty',
                            'invoiced_qty',
                            'invoiced_remaining_qty',
                            'methods_units_id',
                            'selling_price',
                            'discount',
                            'accounting_vats_id',
                            'delivery_date',
                            'tasks_status',
                            'internal_delay',
                            'delivery_status',
                            'invoice_status',
                        ];

    public function order()
    {
        return $this->belongsTo(Orders::class, 'orders_id');
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
        return $this->hasMany(Task::class, 'order_lines_id')->orderBy('ordre');
    }

    public function OrderLineDetails()
    {
        return $this->hasOne(OrderLineDetails::class, 'order_lines_id');
    }

    public function DeliveryLines()
    {
        return $this->hasMany(DeliveryLines::class, 'order_line_id');
    }

    public function InvoiceLines()
    {
        return $this->hasMany(InvoiceLines::class, 'order_line_id');
    }

    public function QualityNonConformity()
    {
        return $this->hasMany(QualityNonConformity::class);
    }

    public function StockMove()
    {
        return $this->hasMany(StockMove::class);
    }

    public function SerialNumbers()
    {
        return $this->hasMany(SerialNumbers::class);
    }

    public function getAllTaskCountAttribute()
    {        
        $taskCount =  $this->Task()->count();
        $subAssemblyCount = $this->SubAssembly()->count();
        return '('. $taskCount .') ('. $subAssemblyCount .')';
    }

    public function TechnicalCut()
    {
        return $this->hasMany(Task::class, 'order_lines_id')
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
        return $this->TechnicalCut->reduce(function ($totalUnitTime, $TechnicalCut) {
        return $totalUnitTime + $TechnicalCut->unit_time;
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
        return $this->hasMany(Task::class, 'order_lines_id')
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

    public function getAveragePercentProgressTaskAttribute()
    {
        $SumPercentTech = $this->TechnicalCut->reduce(function ($SumPercent, $TechnicalCut) {
            if($TechnicalCut->progress() > 100) $TechnicalCutPerCent = 100;
            else  $TechnicalCutPerCent = $TechnicalCut->progress();

            return $SumPercent + $TechnicalCutPerCent;
            },0);

        $SumPercentBOM = $this->BOM->reduce(function ($SumPercent, $BOM) {
            if($BOM->progress() > 100) $BOMPerCent = 100;
            else  $BOMPerCent = $BOM->progress();
                return $SumPercent + $BOMPerCent;
            },0);

        $TotalCountTask = $this->Task()->count();
        if($TotalCountTask <= 0 ) $TotalCountTask = 1;

        return ($SumPercentTech +  $SumPercentBOM)/$TotalCountTask;
    }

    public function getAveragePercentProgressDeleveryAttribute()
    {
        return ($this->delivered_qty / $this->qty)*100;
    }

    public function getAveragePercentProgressInvoiceAttribute()
    {
        return ($this->invoiced_qty / $this->qty)*100;
    }

    public function SubAssembly()
    {
        return $this->hasMany(SubAssembly::class, 'order_lines_id')->orderBy('ordre');
    }

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['orders_id', 'code', 'label', 'statu']);
        // Chain fluent methods for configuration options
    }
}
