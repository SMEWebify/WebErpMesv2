<?php

namespace App\Models\Purchases;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Clause qualité d'achat (référentiel) : texte imprimé en fin de commande
 * d'achat quand une de ses règles correspond à la criticité d'une ligne.
 *
 * Jamais supprimée depuis l'écran : désactivée, elle ne s'applique plus aux
 * commandes en cours ; les commandes émises gardent leur copie du texte.
 */
class PurchaseQualityClause extends Model
{
    use LogsActivity;

    protected $fillable = ['code', 'label', 'text_fr', 'text_en', 'ordre', 'active'];

    protected $casts = [
        'ordre'  => 'integer',
        'active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['label', 'text_fr', 'text_en', 'ordre', 'active'])->logOnlyDirty();
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('ordre')->orderBy('code');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function rules()
    {
        return $this->hasMany(PurchaseQualityClauseRule::class);
    }

    public function orderCopies()
    {
        return $this->hasMany(PurchaseOrderQualityClause::class);
    }
}
