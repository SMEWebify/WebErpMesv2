<?php

namespace App\Models\Products;

use App\Models\File;
use App\Models\Planning\Task;
use App\Models\Planning\Status;
use Spatie\Activitylog\LogOptions;
use App\Models\Companies\Companies;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\QuoteLines;
use App\Models\Methods\MethodsUnits;
use App\Models\Planning\SubAssembly;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsServices;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Products\ProductsQuantityPrice;
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
                            'finishing',
                            'picture',
                            'drawing_file',
                            'stl_file',
                            'svg_file',];

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
        return $this->hasMany(StockLocationProducts::class, 'products_id');
    }

    public function StockLocationProductCount()
    {
        return  $this->Stock_location_product()->count();
    }

    public function getTotalStockMove()
    {
        $stockLocations = $this->Stock_location_product;
        return $stockLocations->sum(function ($stockLocation) {
            return $stockLocation->getCurrentStockMove();
        });
    }

    //https://github.com/SMEWebify/WebErpMesv2/issues/319
    public function getColorStockStatu()
    {
        $stocks = $this->Stock_location_product;
        $colorStatu = 'danger';
        foreach ($stocks as $stock) {
            $currentStock = $stock->getCurrentStockMove();
            $minQty = $stock->mini_qty;

            if ($currentStock < $minQty) {
                return 'danger';
            }
            elseif($currentStock == $minQty) {
                $colorStatu = 'warning';
            }
            elseif($currentStock > $minQty) {
                $colorStatu = 'success';
            }
        }

        return  $colorStatu;
    }

    public function Task()
    {
        return $this->hasMany(Task::class, 'products_id')->orderBy('ordre');
    }

    //https://github.com/SMEWebify/WebErpMesv2/issues/313
    public function preferredSuppliers() {
        return $this->belongsToMany(Companies::class, 'products_preferred_suppliers', 'product_id', 'companies_id')->withTimestamps();
    }

    public function getAllTaskCountAttribute()
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
        return round((($this->getTechnicalCutTotalUnitPricettribute()/$this->getTechnicalCutTotalUnitCostAttribute())-1)*100,2);
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
        return round((($this->getBOMTotalUnitPricettribute()/$this->getBOMTotalUnitCostAttribute())-1)*100,2);
    }

    public function Quotelines()
    {
        return $this->hasMany(QuoteLines::class);
    }

    public function OrderLines()
    {
        return $this->hasMany(OrderLines::class);
    }

    //Get all oder line was not finished and not manufactured for define what is needed for create stock
    //https://github.com/SMEWebify/WebErpMesv2/issues/321
    public function undeliveredOrderLines()
    {
        return $this->hasMany(OrderLines::class, 'product_id')
                ->where(function ($query) {
                    $query->where('delivery_status', '=', 1)
                            ->orWhere('delivery_status', '=', 2);
                })
                ->whereDoesntHave('Task')
                ->whereHas('order', function ($query) {
                    $query->where('type', '=', 1);
                });
                #1 = Not delivered
                #2 = Partly delivered
    }

    //Get sum from undeliveredOrderLines()
    //https://github.com/SMEWebify/WebErpMesv2/issues/321
    public function getTotalUndeliveredQtyWithoutTasksAttribute()
    {
        return $this->undeliveredOrderLines->sum('delivered_remaining_qty');
    }

    //Get all task line was not finished for define what is needed for create stock
    //https://github.com/SMEWebify/WebErpMesv2/issues/321
    public function unFinishedTaskLines()
    {
        $statuses = Status::whereIn('title', ['Open', 'Started', 'In progress'])->get();
        $openStatusId = $statuses->where('title', 'Open')->first()->id ?? null;
        $startedStatusId = $statuses->where('title', 'Started')->first()->id ?? null;
        $inProgressStatusId = $statuses->where('title', 'In progress')->first()->id ?? null;

        return  $this->hasMany(Task::class, 'component_id')
                ->whereIn('status_id', [$openStatusId, $startedStatusId, $inProgressStatusId])
                ->whereNotNull('order_lines_id');
    }

    //Get sum from unFinishedTaskLines()
    //https://github.com/SMEWebify/WebErpMesv2/issues/321
    public function getTotalUnFinishedTaskLinesQtyAttribute()
    {
        $totalQty = 0;
        foreach ($this->unFinishedTaskLines as $task) {
            if ($task->OrderLines) {
                $totalQty += $task->qty * $task->OrderLines->delivered_remaining_qty;
            }
            
        }
        return $totalQty;
    }

    // Relationship with the files associated with the Quote
    public function files()
    {
        return $this->hasMany(File::class);
    }

    public function SubAssembly()
    {
        return $this->hasMany(SubAssembly::class, 'products_id')->orderBy('ordre');
    }

    public function QuantityPrice()
    {
        return $this->hasMany(ProductsQuantityPrice::class);
    }

    public function getQuantityPricesForSupplier($supplierId)
    {
        // Récupérer les prix par quantité pour le fournisseur spécifié
        return $this->QuantityPrice()
                    ->where('companies_id', $supplierId)
                    ->orderBy('min_qty')
                    ->get();
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
