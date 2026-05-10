<?php

namespace App\Models\Integrations;

use Illuminate\Database\Eloquent\Model;

class QontoConnection extends Model
{
    protected $fillable = [
        'tenant_id',
        'access_token',
        'refresh_token',
        'access_token_expires_at',
        'scope',
        'organization_slug',
        'iban',
        'import_bidirectionnel',
        'last_sync_at',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'iban' => 'encrypted',
        'access_token_expires_at' => 'datetime',
        'import_bidirectionnel' => 'boolean',
        'last_sync_at' => 'datetime',
    ];
}
