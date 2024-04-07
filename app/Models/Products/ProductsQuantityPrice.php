<?php

namespace App\Models\Products;

use App\Models\Products\Products;
use App\Models\Companies\Companies;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductsQuantityPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'products_id',
        'companies_id',
        'min_qty',
        'max_qty',
        'price',
    ];

    public function product()
    {
        return $this->belongsTo(Products::class, 'products_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Companies::class);
    }
}
