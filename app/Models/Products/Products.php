<?php

namespace App\Models\Products;

use App\Models\Planning\Task;
use App\Models\Workflow\QuoteLines;
use App\Models\Methods\MethodsUnits;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsServices;
use Illuminate\Database\Eloquent\Model;
use App\Models\Products\StockLocationProducts;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Products extends Model
{
    use HasFactory;

    protected $fillable = ['CODE',
                          'LABEL', 
                           'IND',
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
                           'PICTURE',];

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
        return $this->hasMany(Task::class);
    }

    public function Quotelines()
    {
        return $this->hasMany(QuoteLines::class);
    }

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
    
}
