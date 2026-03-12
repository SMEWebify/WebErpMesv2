<?php

namespace App\Models\Workflow;

use App\Models\Products\Products;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;

class PreOrderLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'pre_order_id',
        'row_index',
        'reference',
        'product',
        'quantity',
        'unit_price',
        'total_price',
        'suggested_product_id',
        'linked_product_id',
        'matching_unit_price',
        'matching_confirmed_at',
    ];

    protected $casts = [
        'matching_confirmed_at' => 'datetime',
    ];

    public function getEffectiveTotalPriceAttribute(): float
    {
        $totalPrice = (float) ($this->total_price ?? 0);
        if ($totalPrice > 0) {
            return $totalPrice;
        }

        return (float) ($this->quantity ?? 0) * (float) ($this->unit_price ?? 0);
    }

    public function getSellingPriceAttribute(): float
    {
        return (float) ($this->unit_price ?? 0);
    }

    public function getFormattedSellingPriceAttribute(): string
    {
        $factory = app('Factory');
        $currency = $factory->curency ?? 'EUR';

        return Number::currency($this->getSellingPriceAttribute(), $currency, config('app.locale'));
    }

    public function getFormattedTotalPriceAttribute(): string
    {
        $factory = app('Factory');
        $currency = $factory->curency ?? 'EUR';

        return Number::currency($this->effective_total_price, $currency, config('app.locale'));
    }

    public function preOrder()
    {
        return $this->belongsTo(PreOrder::class);
    }

    public function suggestedProduct()
    {
        return $this->belongsTo(Products::class, 'suggested_product_id');
    }

    public function linkedProduct()
    {
        return $this->belongsTo(Products::class, 'linked_product_id');
    }

    public function getFormattedMatchingUnitPriceAttribute(): ?string
    {
        if ($this->matching_unit_price === null) {
            return null;
        }

        $factory = app('Factory');
        $currency = $factory->curency ?? 'EUR';

        return Number::currency((float) $this->matching_unit_price, $currency, config('app.locale'));
    }
}
