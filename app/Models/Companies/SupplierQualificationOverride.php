<?php

namespace App\Models\Companies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Passe-droit ponctuel sur un fournisseur non qualifié : vaut pour la seule
 * commande d'achat concernée (création et validation), avec l'état de
 * l'évaluation au moment du forçage (App\Services\Purchases\SupplierQualification).
 */
class SupplierQualificationOverride extends Model
{
    protected $fillable = [
        'companies_id',
        'document_type',
        'document_id',
        'user_id',
        'context',
        'reason',
        'qualification_status',
        'evaluation_status',
        'next_review_at',
    ];

    protected $casts = [
        'next_review_at' => 'date',
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
