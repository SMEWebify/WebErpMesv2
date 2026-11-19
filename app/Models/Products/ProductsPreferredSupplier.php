<?php

namespace App\Models\Products;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductsPreferredSupplier extends Model
{
    use HasFactory;
    // Fillable attributes for mass assignment
    protected $fillable= ['product_id', 
                            'companies_id',
                            'supplier_reference',];

    /**
     * Référence de l'article chez ce fournisseur, ou null.
     */
    public static function referenceFor($productId, $companyId): ?string
    {
        if (! $productId || ! $companyId) {
            return null;
        }

        $reference = static::query()
            ->where('product_id', $productId)
            ->where('companies_id', $companyId)
            ->value('supplier_reference');

        return $reference === null || trim($reference) === '' ? null : $reference;
    }

    public function QuantityPrice()
    {
        return $this->hasMany(ProductsQuantityPrice::class, 'product_id', 'companies_id',);
    }
}
