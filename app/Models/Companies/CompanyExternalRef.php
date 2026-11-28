<?php

namespace App\Models\Companies;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Identifiant d'une société chez un logiciel tiers (`system` = alma, radquote…). */
class CompanyExternalRef extends Model
{
    protected $fillable = ['companies_id', 'system', 'external_id'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Companies::class, 'companies_id');
    }
}
