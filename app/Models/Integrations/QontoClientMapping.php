<?php

namespace App\Models\Integrations;

use Illuminate\Database\Eloquent\Model;

class QontoClientMapping extends Model
{
    protected $fillable = [
        'tenant_id',
        'wem_client_id',
        'qonto_client_id',
        'sync_status',
        'matching_score',
        'last_sync_at',
        'error_message',
    ];

    protected $casts = [
        'last_sync_at' => 'datetime',
    ];
}
