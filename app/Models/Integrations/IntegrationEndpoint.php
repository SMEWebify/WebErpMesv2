<?php

namespace App\Models\Integrations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class IntegrationEndpoint extends Model
{
    public const DIRECTION_INBOUND = 'inbound';
    public const DIRECTION_OUTBOUND = 'outbound';

    public const AUTH_NONE = 'none';
    public const AUTH_BEARER = 'bearer';
    public const AUTH_HMAC = 'hmac';
    public const AUTH_BEARER_HMAC = 'bearer_hmac';

    public const DEFAULT_RETRY_BACKOFF = [60, 300, 900, 1800, 3600];

    protected $fillable = [
        'name',
        'system_code',
        'direction',
        'url',
        'auth_method',
        'bearer_token',
        'hmac_secret',
        'hmac_header',
        'timestamp_header',
        'timestamp_tolerance_s',
        'verify_ssl',
        'events',
        'is_active',
        'retry_max',
        'retry_backoff',
        'last_success_at',
        'last_error_at',
        'last_error_message',
    ];

    protected $casts = [
        'bearer_token' => 'encrypted',
        'hmac_secret' => 'encrypted',
        'events' => 'array',
        'retry_backoff' => 'array',
        'is_active' => 'boolean',
        'verify_ssl' => 'boolean',
        'timestamp_tolerance_s' => 'integer',
        'retry_max' => 'integer',
        'last_success_at' => 'datetime',
        'last_error_at' => 'datetime',
    ];

    /**
     * Exclus de toArray()/toJson() pour empêcher qu'un Log::info(model)
     * ou dd(model) ne divulgue les secrets déchiffrés côté logs applicatifs.
     * L'UI admin (regenerate/create) lit explicitement $endpoint->bearer_token
     * une fois pour l'afficher à la copie — c'est le seul chemin autorisé.
     */
    protected $hidden = [
        'bearer_token',
        'hmac_secret',
    ];

    public function deliveries(): HasMany
    {
        return $this->hasMany(IntegrationDelivery::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInbound(Builder $query): Builder
    {
        return $query->where('direction', self::DIRECTION_INBOUND);
    }

    public function scopeOutbound(Builder $query): Builder
    {
        return $query->where('direction', self::DIRECTION_OUTBOUND);
    }

    public function scopeForSystem(Builder $query, string $systemCode): Builder
    {
        return $query->where('system_code', $systemCode);
    }

    public function requiresBearer(): bool
    {
        return in_array($this->auth_method, [self::AUTH_BEARER, self::AUTH_BEARER_HMAC], true);
    }

    public function requiresHmac(): bool
    {
        return in_array($this->auth_method, [self::AUTH_HMAC, self::AUTH_BEARER_HMAC], true);
    }

    public function subscribesTo(string $eventType): bool
    {
        $events = $this->events ?? [];
        return $events === [] || in_array($eventType, $events, true);
    }

    public function regenerateSecrets(): void
    {
        if ($this->requiresBearer()) {
            $this->bearer_token = Str::random(64);
        }
        if ($this->requiresHmac()) {
            $this->hmac_secret = Str::random(64);
        }
    }
}
