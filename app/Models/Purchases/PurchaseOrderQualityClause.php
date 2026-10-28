<?php

namespace App\Models\Purchases;

use Illuminate\Database\Eloquent\Model;

/**
 * Copie d'une clause qualité sur une commande d'achat : texte figé à l'émission
 * (App\Services\Purchases\PurchaseQualityClauses), indépendant du référentiel.
 */
class PurchaseOrderQualityClause extends Model
{
    protected $fillable = ['purchases_id', 'purchase_quality_clause_id', 'code', 'label', 'text_fr', 'text_en', 'ordre'];

    protected $casts = [
        'ordre' => 'integer',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchases::class, 'purchases_id');
    }

    /** Texte dans la langue demandée, le français à défaut de traduction. */
    public function text(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return $locale !== 'fr' && filled($this->text_en) ? $this->text_en : $this->text_fr;
    }
}
