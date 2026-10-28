<?php

namespace App\Models\Workflow;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Conversion d'un devis en commande malgré des opérations obligatoires
 * absentes de la gamme : qui, pourquoi, et ce qui manquait à ce moment-là.
 */
class RequiredOperationsOverride extends Model
{
    protected $fillable = ['quotes_id', 'document_type', 'document_id', 'affair_category_id', 'user_id', 'reason', 'missing'];

    protected $casts = [
        'missing' => 'array',
    ];

    public function quote()
    {
        return $this->belongsTo(Quotes::class, 'quotes_id');
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
