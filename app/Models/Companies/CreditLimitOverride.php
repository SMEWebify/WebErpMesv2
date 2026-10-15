<?php

namespace App\Models\Companies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Passe-droit ponctuel sur le plafond d'encours : vaut pour le seul document
 * créé (commande ou BL), avec l'état de l'encours au moment du forçage.
 */
class CreditLimitOverride extends Model
{
    protected $fillable = [
        'companies_id',
        'document_type',
        'document_id',
        'user_id',
        'reason',
        'outstanding',
        'credit_limit',
        'projected',
    ];

    protected $casts = [
        'outstanding'  => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'projected'    => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Companies::class, 'companies_id');
    }

    public function document()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
