<?php

namespace App\Models\Workflow;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Motif de perte d'un devis ou d'une opportunité (prix, délai, concurrent…).
 * Demandé au passage en Perdu par App\Services\Workflow\LossReasonRecorder.
 *
 * Un motif n'est jamais supprimé depuis l'écran : désactivé, il sort des listes
 * de choix mais reste affiché sur les documents qui le portent.
 */
class LossReason extends Model
{
    protected $fillable = ['code', 'label', 'ordre', 'active'];

    protected $casts = [
        'ordre'  => 'integer',
        'active' => 'boolean',
    ];

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('ordre')->orderBy('label');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * Choix proposés à la saisie : les motifs actifs, plus celui déjà porté par
     * le document même s'il a été désactivé depuis.
     */
    public static function choices(?int $currentId = null)
    {
        return static::query()
            ->where(fn ($q) => $q->where('active', true)
                ->when($currentId, fn ($q) => $q->orWhere('id', $currentId)))
            ->ordered()
            ->get(['id', 'code', 'label', 'active']);
    }

    public function quotes()
    {
        return $this->hasMany(Quotes::class);
    }

    public function opportunities()
    {
        return $this->hasMany(Opportunities::class);
    }
}
