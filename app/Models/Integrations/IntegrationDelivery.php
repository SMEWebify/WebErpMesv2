<?php

namespace App\Models\Integrations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationDelivery extends Model
{
    public const DIRECTION_IN = 'in';
    public const DIRECTION_OUT = 'out';

    protected $fillable = [
        'integration_endpoint_id',
        'direction',
        'event_id',
        'event_type',
        'http_status',
        'duration_ms',
        'payload',
        'response_body',
        'error',
        'occurred_at',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'http_status' => 'integer',
        'duration_ms' => 'integer',
        'occurred_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(IntegrationEndpoint::class, 'integration_endpoint_id');
    }

    public function scopeInbound(Builder $query): Builder
    {
        return $query->where('direction', self::DIRECTION_IN);
    }

    public function scopeOutbound(Builder $query): Builder
    {
        return $query->where('direction', self::DIRECTION_OUT);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('processed_at');
    }

    public function scopeForEndpoint(Builder $query, int $endpointId): Builder
    {
        return $query->where('integration_endpoint_id', $endpointId);
    }

    public function scopeRecent(Builder $query, int $limit = 200): Builder
    {
        return $query->orderByDesc('created_at')->limit($limit);
    }

    public function markProcessed(?int $httpStatus = null, ?string $responseBody = null, ?int $durationMs = null): void
    {
        $this->fill([
            'processed_at' => now(),
            'http_status' => $httpStatus ?? $this->http_status,
            'response_body' => $responseBody ?? $this->response_body,
            'duration_ms' => $durationMs ?? $this->duration_ms,
            // Efface le marqueur "sending" posé au moment du dispatch outbound
            // (voir N2PClient::sendPayload) : si on ne le fait pas, une delivery
            // OK reste marquée en erreur pour toujours dans l'UI.
            'error' => null,
        ])->save();
    }

    public function markFailed(string $error, ?int $httpStatus = null): void
    {
        $this->fill([
            'error' => $error,
            'http_status' => $httpStatus ?? $this->http_status,
        ])->save();
    }
}
