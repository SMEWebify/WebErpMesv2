<?php

namespace App\Models\Workflow;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function preOrder()
    {
        return $this->belongsTo(PreOrder::class);
    }
}

