<?php

namespace App\Models\Purchases;

use App\Models\Planning\Task;
use Illuminate\Support\Number;
use App\Models\Products\Products;
use Spatie\Activitylog\LogOptions;
use App\Models\Purchases\Purchases;
use App\Models\Methods\MethodsUnits;
use App\Models\Products\StockLocation;
use Illuminate\Database\Eloquent\Model;
use App\Models\Accounting\AccountingVat;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Purchases\PurchaseReceiptLines;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseLines extends Model
{
    use HasFactory, LogsActivity;

    // Fillable attributes for mass assignment
    protected $fillable= ['purchases_id', 
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
                            'accounting_vats_id',
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

    public function VAT()
    {
        return $this->belongsTo(AccountingVat::class, 'accounting_vats_id');
    }

    public function stockLocation()
    {
        return $this->belongsTo(StockLocation::class, 'stock_locations_id');
    }

    public function getTotalAttribute()
    {
        $factory = app('Factory'); 
        $currency = $factory->curency ?? 'EUR';
        
        $price = $this->selling_price;
        $qty = $this->qty;
        $discount = $this->discount ?? 0;
        
        $total = $price * $qty;
        $discountedTotal = $total - ($total * ($discount / 100));

        return Number::currency($discountedTotal, $currency, config('app.locale'));
    }

    /**
     * Get the formatted total price attribute.
     *
     * This method retrieves the total price attribute, formats it as a currency
     * using the specified factory currency and application locale, and returns
     * the formatted value.
     *
     * @return string The formatted total price.
     */
    public function getFormattedSellingPriceAttribute()
    {
        $factory = app('Factory'); 
        $currency = $factory->curency ?? 'EUR';
        return Number::currency($this->selling_price, $currency, config('app.locale'));
    }

    /**
     * Get the formatted creation date of the line.
     *
     * This accessor method returns the creation date of line
     * formatted as 'day month year' (e.g., '01 January 2023').
     *
     * @return string The formatted creation date.
     */
    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly([ 'code', 'label', 'statu']);
        // Chain fluent methods for configuration options
    }
}
