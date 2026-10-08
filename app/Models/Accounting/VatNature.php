<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Nature de TVA d'une ligne (axe « article/ligne » de la matrice) et levier
 * d'imputation analytique. Table de référence administrable.
 * Seed : fabrication, prestation, port, acompte, matiere, marchandise,
 * sous_traitance, frais, immobilisation.
 */
class VatNature extends Model
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
