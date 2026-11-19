<?php

namespace App\Models\Workflow;

use Illuminate\Database\Eloquent\Model;

/**
 * Élément structuré d'une ligne configurée : option choisie (avec son supplément),
 * cote saisie, exigence, ou référence chez le fournisseur. Polymorphe : ligne de
 * devis ou de commande (et plus tard d'achat).
 */
class LineConfigurationItem extends Model
{
    public const KIND_OPTION = 'option';
    public const KIND_DIMENSION = 'dimension';
    public const KIND_REQUIREMENT = 'requirement';
    public const KIND_SUPPLIER = 'supplier';

    protected $fillable = [
        'kind',
        'code',
        'label',
        'value',
        'unit',
        'unit_price',
        'sort',
    ];

    protected $casts = [
        'unit_price' => 'float',
    ];

    public function configurable()
    {
        return $this->morphTo();
    }
}
