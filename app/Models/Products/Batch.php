<?php

namespace App\Models\Products;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Batch extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'product_id',
        'production_date',
        'expiration_date',
        'closed_at',
    ];

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function stockMoves()
    {
        return $this->hasMany(StockMove::class);
    }

    public function serialNumbers()
    {
        return $this->hasMany(SerialNumbers::class);
    }
}
