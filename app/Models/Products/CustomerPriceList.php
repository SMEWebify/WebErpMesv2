<?php

namespace App\Models\Products;

use App\Models\Companies\Companies;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerPriceList extends Model
{
    use HasFactory;

    protected $fillable = [
        'products_id',
        'companies_id',
        'customer_type',
        'min_qty',
        'max_qty',
        'price',
    ];

    public function product()
    {
        return $this->belongsTo(Products::class, 'products_id');
    }

    public function company()
    {
        return $this->belongsTo(Companies::class, 'companies_id');
    }
}
