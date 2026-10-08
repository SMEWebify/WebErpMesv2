<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Ligne de la matrice de TVA ACHATS : (régime, nature) → code de TVA + comptes.
 * `manual_vat` = l'écriture TVA d'autoliquidation est laissée à la saisie manuelle.
 */
class AccountingVatPurchaseRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'vat_regime_id',
        'vat_nature_id',
        'accounting_vats_id',
        'purchase_account',
        'vat_account',
        'vat_account_autoliq',
        'manual_vat',
    ];

    protected $casts = [
        'manual_vat' => 'boolean',
    ];

    public function regime()
    {
        return $this->belongsTo(VatRegime::class, 'vat_regime_id');
    }

    public function nature()
    {
        return $this->belongsTo(VatNature::class, 'vat_nature_id');
    }

    public function VAT()
    {
        return $this->belongsTo(AccountingVat::class, 'accounting_vats_id');
    }
}
