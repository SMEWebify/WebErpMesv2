<?php

namespace App\Models\Workflow;

use App\Models\Planning\Task;
use App\Models\workflow\Orders;
use App\Models\Products\Products;
use App\Models\Methods\MethodsUnits;
use Illuminate\Database\Eloquent\Model;
use App\Models\Accounting\AccountingVat;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderLines extends Model
{
    protected $fillable = ['orders_id', 
                            'ORDRE', 
                            'CODE',
                            'product_id',
                            'LABEL',
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
        return $this->hasMany(Task::class)->orderBy('ORDER');
    }

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
}
