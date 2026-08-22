<?php

namespace App\Models\Integrations;

use Illuminate\Database\Eloquent\Model;

/**
 * Position de lecture d'un flux PDP (cf. migration create_pdp_sync_cursors_table).
 */
class PdpSyncCursor extends Model
{
    protected $table = 'pdp_sync_cursors';

    /** Flux synchronisés. */
    public const STREAM_INVOICES_IN = 'invoices_in';   // factures fournisseurs reçues
    public const STREAM_EVENTS      = 'invoice_events'; // cycle de vie de nos factures émises

    protected $fillable = ['provider', 'stream', 'tenant_id', 'last_id', 'synced_at'];

    protected $casts = [
        'last_id'   => 'integer',
        'tenant_id' => 'integer',
        'synced_at' => 'datetime',
    ];

    public static function positionOf(string $provider, string $stream, int $tenantId = 0): int
    {
        return (int) static::query()
            ->where('provider', $provider)
            ->where('stream', $stream)
            ->where('tenant_id', $tenantId)
            ->value('last_id');
    }

    /**
     * Avance le curseur. Ne recule jamais : deux exécutions concurrentes de la
     * synchronisation ne peuvent pas se faire rejouer mutuellement un flux.
     */
    public static function advance(string $provider, string $stream, int $lastId, int $tenantId = 0): void
    {
        $cursor = static::firstOrNew([
            'provider'  => $provider,
            'stream'    => $stream,
            'tenant_id' => $tenantId,
        ]);

        if ($lastId > (int) $cursor->last_id) {
            $cursor->last_id = $lastId;
        }

        $cursor->synced_at = now();
        $cursor->save();
    }
}
