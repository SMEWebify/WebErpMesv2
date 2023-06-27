<?php

namespace App\Models\Planning;

use App\Models\Products\Products;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\QuoteLines;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubAssembly extends Model
{
    use HasFactory;


    protected $fillable = ['ordre',
                            'quote_lines_id',
                            'order_lines_id',
                            'products_id',
                            'child_id',
                            'qty',
                            'unit_price'];

    public function QuoteLines()
    {
        return $this->belongsTo(QuoteLines::class, 'quote_lines_id');
    }

    public function OrderLines()
    {
        return $this->belongsTo(OrderLines::class, 'order_lines_id');
    }

    public function Products()
    {
        return $this->belongsTo(Products::class, 'products_id');
    }

    public function Child()
    {
        return $this->belongsTo(Products::class, 'child_id');
    }

}
