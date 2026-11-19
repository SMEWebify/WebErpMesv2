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
                            'supplier_reference',
                            'supplier_label',];

    /**
     * Référence et désignation de l'article chez ce fournisseur (valeurs vides
     * rendues null), en une requête.
     *
     * @return array{supplier_reference: ?string, supplier_label: ?string}
     */
    public static function catalogueFor($productId, $companyId): array
    {
        $row = ($productId && $companyId)
            ? static::query()
                ->where('product_id', $productId)
                ->where('companies_id', $companyId)
                ->first(['supplier_reference', 'supplier_label'])
            : null;

        $clean = fn ($value) => $value === null || trim($value) === '' ? null : $value;

        return [
            'supplier_reference' => $clean($row?->supplier_reference),
            'supplier_label'     => $clean($row?->supplier_label),
        ];
    }

    public function QuantityPrice()
    {
        return $this->hasMany(ProductsQuantityPrice::class, 'product_id', 'companies_id',);
    }
}
