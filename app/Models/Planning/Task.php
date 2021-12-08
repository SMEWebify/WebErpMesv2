<?php

namespace App\Models\Planning;

use App\Models\User;
use App\Models\Products\Products;
use App\Models\Workflow\QuoteLines;
use App\Models\Methods\MethodsTools;
use App\Models\Methods\MethodsUnits;
use App\Models\Methods\MethodsServices;
use Illuminate\Database\Eloquent\Model;
use App\Models\Quality\QualityNonConformity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Planning\Status;

class Task extends Model
{
    use HasFactory;

    protected $fillable = ['label', 
                            'ORDER',
                            'quote_lines_id',
                            'order_lines_id',
                            'products_id',
                            'methods_services_id',  
                            'component_id',
                            'SETING_TIME', 
                            'UNIT_TIME', 
                            'REMAINING_TIME', 
                            'ADVANCEMENT', 
                            'status_id', 
                            'TYPE',
                            'DELAY',
                            'qty',
                            'qty_init',
                            'qty_aviable',
                            'UNIT_COST',
                            'UNIT_PRICE',
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
                            'material', 
                            'thickness', 
                            'weight', 
                            'quality_non_conformities_id',
                            'methods_tools_id'];

    public function service()
    {
        return $this->belongsTo(MethodsServices::class, 'methods_services_id');
    }

    public function QuoteLines()
    {
        return $this->belongsTo(QuoteLines::class, 'quote_lines_id');
    }

  /*  public function Order()
    {
        return $this->hasManyThrough('App\Models\Workflow\Orders', 'App\Models\Workflow\OrderLines');
    }
    */

    public function OrderLines()
    {
        return $this->belongsTo(QuoteLines::class, 'order_lines_id');
    }

    public function Products()
    {
        return $this->belongsTo(Products::class, 'products_id');
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
        return $this->belongsTo(QualityNonConformity::class, 'quality_non_conformities_id');
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

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
}
