<?php

namespace App\Models\Products;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ProductPriceHistory extends Model
{
    protected $table = 'product_price_histories';

    protected $fillable = ['products_id', 'type', 'price', 'started_at', 'ended_at', 'user_id'];

    protected $casts = [
        'started_at' => 'date',
        'ended_at'   => 'date',
        'price'      => 'decimal:3',
    ];

    public function product()
    {
        return $this->belongsTo(Products::class, 'products_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
