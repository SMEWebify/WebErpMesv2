<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Régime de TVA d'un tiers (axe « client/fournisseur » de la matrice).
 * Table de référence administrable. Seed : FR, FRANCHISE, UE, EXPORT, AUTOLIQ.
 */
class VatRegime extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'label'];

    public function salesRules()
    {
        return $this->hasMany(AccountingVatSalesRule::class);
    }

    public function purchaseRules()
    {
        return $this->hasMany(AccountingVatPurchaseRule::class);
    }
}
