<?php

namespace App\Models\Purchases;

use App\Models\Planning\Task;
use Illuminate\Support\Number;
use Illuminate\Database\Eloquent\Model;
use App\Models\Purchases\PurchasesQuotation;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseQuotationLines extends Model
{
    use HasFactory;

    // Fillable attributes for mass assignment
    protected $fillable= ['purchases_quotation_id', 
        'tasks_id',
        'code',
        'product_id',
        'label',
        'ordre',
        'qty_to_order',
        'unit_price',
        'total_price',
        'lead_time_days',
        'conditions',
        'supplier_score',
        'supplier_comment',
        'qty_accepted',
        'canceled_qty',
    ];

    public function tasks()
    {
        return $this->belongsTo(Task::class, 'tasks_id');
    }

    public function purchaseQuotation()
    {
        return $this->belongsTo(PurchasesQuotation::class, 'purchases_quotation_id');
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\Products\Products::class, 'product_id');
    }

    /**
     * Get the formatted unit price attribute.
     *
     * This method retrieves the unit price attribute, formats it as a currency
     * using the specified factory currency and application locale, and returns
     * the formatted value.
     *
     * @return string The formatted unit price.
     */
    public function getFormattedUnitPriceAttribute()
    {
        $factory = app('Factory'); 
        $currency = $factory->curency ?? 'EUR';
        return Number::currency($this->unit_price, $currency, config('app.locale'));
    }

    /**
     * Get the formatted selling price attribute.
     *
     * @return string The formatted selling price.
     */
    public function getFormattedSellingPriceAttribute()
    {
        return $this->formatted_unit_price;
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
    public function getFormattedTotalPriceAttribute()
    {
        $factory = app('Factory'); 
        $currency = $factory->curency ?? 'EUR';
        return Number::currency($this->total_price, $currency, config('app.locale'));

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
}
