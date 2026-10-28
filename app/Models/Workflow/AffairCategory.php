<?php

namespace App\Models\Workflow;

use App\Models\Methods\MethodsServices;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Catégorie d'affaire (débitmétrie, pyrométrie, accessoire…). Posée sur
 * l'opportunité, reprise par le devis créé depuis elle puis par la commande
 * issue du devis (hooks `creating` de Quotes et Orders).
 *
 * Une catégorie n'est jamais supprimée depuis l'écran : désactivée, elle sort
 * des listes de choix mais reste affichée sur les documents qui la portent.
 */
class AffairCategory extends Model
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
     * Choix proposés à la saisie : les catégories actives, plus celle déjà
     * portée par le document même si elle a été désactivée depuis.
     */
    public static function choices(?int $currentId = null)
    {
        return static::query()
            ->where(fn ($q) => $q->where('active', true)
                ->when($currentId, fn ($q) => $q->orWhere('id', $currentId)))
            ->ordered()
            ->get(['id', 'code', 'label', 'active']);
    }

    /** Liste du filtre des index : toutes les catégories, actives ou non. */
    public static function forFilter(): array
    {
        return static::query()->ordered()->get(['id', 'code', 'label', 'active'])
            ->map(fn (self $c) => ['id' => $c->id, 'code' => $c->code, 'label' => $c->label, 'active' => $c->active])
            ->all();
    }

    /** Opérations (postes de charge) que la gamme de chaque ligne article doit contenir. */
    public function requiredServices()
    {
        return $this->belongsToMany(MethodsServices::class, 'affair_category_required_services', 'affair_category_id', 'methods_services_id')
            ->withTimestamps();
    }

    public function opportunities()
    {
        return $this->hasMany(Opportunities::class);
    }

    public function quotes()
    {
        return $this->hasMany(Quotes::class);
    }

    public function orders()
    {
        return $this->hasMany(Orders::class);
    }
}
