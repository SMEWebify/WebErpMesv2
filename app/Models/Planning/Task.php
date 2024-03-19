<?php

namespace App\Models\Planning;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Planning\Status;
use App\Models\Products\Products;
use App\Models\Products\StockMove;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\QuoteLines;
use App\Models\Methods\MethodsTools;
use App\Models\Methods\MethodsUnits;
use App\Models\Methods\MethodsServices;
use App\Models\Planning\TaskActivities;
use App\Models\Purchases\PurchaseLines;
use Illuminate\Database\Eloquent\Model;
use App\Models\Methods\MethodsRessources;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Quality\QualityNonConformity;
use Symfony\Component\Validator\Constraints\NotNull;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Task extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['label', 
                            'ordre',
                            'quote_lines_id',
                            'order_lines_id',
                            'products_id',
                            'sub_assembly_id',
                            'methods_services_id',  
                            'component_id',
                            'seting_time', 
                            'unit_time', 
                            'remaining_time', 
                            'status_id', 
                            'type',
                            'delay',
                            'qty',
                            'qty_init',
                            'qty_aviable',
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
                            'end_date',
                            'not_recalculate',
                            'material', 
                            'thickness', 
                            'weight', 
                            'methods_tools_id'];

    protected $appends = ["open"];

    public function service()
    {
        return $this->belongsTo(MethodsServices::class, 'methods_services_id');
    }

    public function resources() {
        return $this->belongsToMany(MethodsRessources::class, 'task_resources')
                    ->withPivot(['autoselected_ressource', 'userforced_ressource'])
                    ->withTimestamps();
    }

    public function QuoteLines()
    {
        return $this->belongsTo(QuoteLines::class, 'quote_lines_id');
    }

    public function OrderLines()
    {
        return $this->belongsTo(OrderLines::class, 'order_lines_id');
    }

    public function purchaseLines()
    {
        return $this->hasMany(PurchaseLines::class, 'tasks_id');
    }

    public function StockMove()
    {
        return $this->hasMany(StockMove::class);
    }

    public function Products()
    {
        return $this->belongsTo(Products::class, 'products_id');
    }

    public function SubAssembly() //https://github.com/SMEWebify/WebErpMesv2/issues/334
    {
        return $this->belongsTo(SubAssembly::class, 'sub_assembly_id');
    }

    public function Component()
    {
        return $this->belongsTo(Products::class, 'component_id');
    }
    
    public function Unit()
    {
        return $this->belongsTo(MethodsUnits::class, 'methods_units_id');
    }

    public function QualityNonConformity()
    {
        return $this->hasMany(QualityNonConformity::class);
    }

    public function MethodsTools()
    {
        return $this->belongsTo(MethodsTools::class, 'methods_tools_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public function GetOrderQtyLine()
    {
        $OrderLine = OrderLines::find($this->order_lines_id);
        if(empty($OrderLine->qty)) $LineQty = 0;
        else $LineQty = $OrderLine->qty;
        return $LineQty;
    }

    public function ProductTime()
    {
        return $this->GetOrderQtyLine()*$this->unit_time;
    }

    public function Margin()
    {
        return round((($this->unit_price/$this->unit_cost)-1)*100,2);
    }

    public function TotalTime()
    {
        return $this->ProductTime()+$this->seting_time;
    }

    public function taskActivities()
    {
        return $this->hasMany(TaskActivities::class);
    }

    public function getTotalLogStartTime()
    {
        $current_date_time = Carbon::now()->toDateTimeString();
        return   TaskActivities::where('task_id', $this->id)
                                ->where('type', 1)
                                ->sum(DB::raw("TIMESTAMPDIFF(SECOND, timestamp, '". $current_date_time ."')"));
    }

    public function getTotalLogEndTime()
    {
        $current_date_time = Carbon::now()->toDateTimeString();
        return   TaskActivities::where('task_id', $this->id)
                                ->where(function (Builder $query) {
                                    return $query->where('type', 2)
                                                ->orWhere('type', 3);
                                })
                                ->sum(DB::raw("TIMESTAMPDIFF(SECOND, timestamp, '". $current_date_time ."')"));
    }

    public function getTotalLogTime()
    {
        return   round(($this->getTotalLogStartTime()-$this->getTotalLogEndTime())/3600,2);
    }

    public function progress()
    {
        if($this->TotalTime() <= 0){
            return 0;
        }
        return   round($this->getTotalLogTime()/$this->TotalTime()*100,2);
    }

    public function getTotalLogGoodQt()
    {
        return   TaskActivities::where('task_id', $this->id)
                                ->where('type', 4)
                                ->sum('good_qt');
    }

    public function getTotalLogBadQt()
    {
        return   TaskActivities::where('task_id', $this->id)
                                ->where('type', 5)
                                ->sum('bad_qt');
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

    public function getOpenAttribute(){
        return true;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['code', 'quote_lines_id', 'order_lines_id', 'products_id']);
        // Chain fluent methods for configuration options
    }
}
