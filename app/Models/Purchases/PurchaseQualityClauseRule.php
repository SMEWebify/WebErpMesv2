<?php

namespace App\Models\Purchases;

use App\Services\Purchases\PurchaseCriticality;
use Illuminate\Database\Eloquent\Model;

/**
 * Règle d'application d'une clause : chaque critère renseigné doit être égal à
 * celui de la ligne d'achat ; un critère vide accepte toute valeur.
 */
class PurchaseQualityClauseRule extends Model
{
    protected $fillable = ['purchase_quality_clause_id', 'certificate_type', 'construction_code', 'nuclear_level'];

    public function clause()
    {
        return $this->belongsTo(PurchaseQualityClause::class, 'purchase_quality_clause_id');
    }

    /**
     * @param  array<string, string|null>  $criticality  certificate_type, construction_code, nuclear_level
     */
    public function matches(array $criticality): bool
    {
        $constrained = false;

        foreach (PurchaseCriticality::FIELDS as $field) {
            if ($this->{$field} === null || $this->{$field} === '') {
                continue;
            }
            $constrained = true;
            if (($criticality[$field] ?? null) !== $this->{$field}) {
                return false;
            }
        }

        // Une règle sans critère ne s'applique à rien : elle imprimerait la clause
        // sur toutes les commandes, y compris celles d'avant la fonctionnalité.
        return $constrained;
    }
}
