<?php

namespace App\Models\Products;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductsPreferredSupplier extends Model
{
    use HasFactory;
    protected $fillable = ['product_id', 
                            'companies_id',];

    public function QuantityPrice()
    {
        return $this->hasMany(ProductsQuantityPrice::class, 'product_id', 'companies_id',);
    }
}
