<?php

namespace App\Models\Workflow;

use App\Models\Planning\Task;
use App\Models\Workflow\Orders;
use App\Models\Products\Products;
use App\Models\Methods\MethodsUnits;
use Illuminate\Database\Eloquent\Model;
use App\Models\Accounting\AccountingVat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

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
        return $this->hasMany(Task::class)->orderBy('ordre');
    }

    public function getTaskCountAttribute()
    {
        return $this->Task()->count();
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

    public function BOM()
    {
        return $this->hasMany(Task::class, 'order_lines_id')
                    ->where(function (Builder $query) {
                        return $query->where('type', 3)
                                    ->orWhere('type','=', 4)
                                    ->orWhere('type','=', 5)
                                    ->orWhere('type','=', 6)
                                    ->orWhere('type','=', 8);
                    })
                    ->orderBy('ordre');
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
