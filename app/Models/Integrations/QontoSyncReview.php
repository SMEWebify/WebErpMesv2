<?php

namespace App\Models\Integrations;

use Illuminate\Database\Eloquent\Model;

class QontoSyncReview extends Model
{
    protected $fillable = [
        'tenant_id',
        'wem_client_id',
        'qonto_client_id',
        'matching_score',
        'candidate_payload',
        'status',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'candidate_payload' => 'array',
        'resolved_at' => 'datetime',
    ];
}
