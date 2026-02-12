<?php

namespace App\Models\Workflow;

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
}
