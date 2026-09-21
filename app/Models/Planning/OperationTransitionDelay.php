<?php

namespace App\Models\Planning;

use App\Models\Methods\MethodsServices;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Délai de transfert entre deux opérations consécutives d'une gamme.
 *
 * Une paire absente veut dire « pas de surcharge » — le calcul retombe sur
 * `config('planning.inter_operation_hours')`. Zéro n'est donc pas une valeur
 * neutre : elle **force** l'absence de délai entre ces deux opérations même
 * si un défaut global est configuré à l'atelier.
 */
class OperationTransitionDelay extends Model
{
    use HasFactory;

    protected $fillable = ['from_service_id', 'to_service_id', 'transfer_hours'];

    protected $casts = ['transfer_hours' => 'float'];

    public function fromService(): BelongsTo
    {
        return $this->belongsTo(MethodsServices::class, 'from_service_id');
    }

    public function toService(): BelongsTo
    {
        return $this->belongsTo(MethodsServices::class, 'to_service_id');
    }
}
