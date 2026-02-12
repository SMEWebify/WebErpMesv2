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

    public function preOrder()
    {
        return $this->belongsTo(PreOrder::class);
    }
}

