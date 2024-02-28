<?php

namespace App\Models\Purchases;

use App\Models\Planning\Task;
use App\Models\Products\Products;
use App\Models\Purchases\Purchases;
use App\Models\Methods\MethodsUnits;
use App\Models\Products\StockLocation;
use Illuminate\Database\Eloquent\Model;
use App\Models\Purchases\PurchaseReceiptLines;
use App\Models\Accounting\AccountingAllocation;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseLines extends Model
{
    use HasFactory;

    protected $fillable = ['purchases_id', 
                            'tasks_id', 
                            'ordre',
                            'code',
                            'product_id',
                            'label',
                            'supplier_ref',
                            'qty',
                            'selling_price',
                            'discount',
                            'unit_price_after_discount',
                            'total_selling_price',
                            'receipt_qty',
                            'invoiced_qty',
                            'methods_units_id',
                            'accounting_allocation_id',
                            'stock_locations_id',
                        ];

    public function tasks()
    {
        return $this->belongsTo(Task::class, 'tasks_id');
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function purchase()
    {
        return $this->belongsTo(Purchases::class, 'purchases_id');
    }

    public function purchaseReceiptLines()
    {
        return $this->hasMany(PurchaseReceiptLines::class, 'purchase_line_id');
    }

    public function unit()
    {
        return $this->belongsTo(MethodsUnits::class, 'methods_units_id');
    }

    public function allocation()
    {
        return $this->belongsTo(AccountingAllocation::class, 'accounting_allocation_id');
    }

    public function stockLocation()
    {
        return $this->belongsTo(StockLocation::class, 'stock_locations_id');
    }

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
}
