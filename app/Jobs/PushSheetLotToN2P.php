<?php

namespace App\Jobs;

use App\Models\Integrations\IntegrationEndpoint;
use App\Services\N2P\N2PClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class PushSheetLotToN2P implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;

    public $backoff = [60, 300, 900, 1800, 3600];

    /**
     * Fenêtre de dédup (secondes). Deux dispatchs successifs du MÊME
     * external_ref à moins de 5 min d'écart sont fusionnés — évite les
     * doublons quand l'observer se déclenche plusieurs fois de suite
     * pour la même réception (corrections, retries transport, backfill).
     */
    public $uniqueFor = 300;

    /**
     * $payload est déjà construit par le SheetLotPayloadBuilder au moment du
     * trigger observer — snapshot insensible aux mutations ultérieures.
     */
    public function __construct(public readonly array $payload)
    {
    }

    /**
     * Dédup queue par external_ref du lot. Si aucun ref (payload malformé)
     * on retombe sur un hash — pire cas : pas de dédup, comportement legacy.
     */
    public function uniqueId(): string
    {
        $ref = $this->payload['lots'][0]['external_ref'] ?? null;
        return is_string($ref) && $ref !== '' ? $ref : sha1(serialize($this->payload));
    }

    public function handle(): void
    {
        $endpoint = IntegrationEndpoint::query()
            ->forSystem('n2p')
            ->outbound()
            ->active()
            ->first();

        if (! $endpoint) {
            // Endpoint désactivé côté admin = décision opérationnelle explicite.
            // On sort en no-op — retenter 5 fois n'y changera rien et ça
            // consommerait ~2h de backoff pour un problème de config.
            Log::channel('n2p')->info('SheetLot push skipped: no active N2P outbound endpoint', [
                'unique_id' => $this->uniqueId(),
            ]);
            return;
        }

        (new N2PClient($endpoint))->pushSheetLots($this->payload);
    }

    public function failed(Throwable $exception): void
    {
        Log::channel('n2p')->error('SheetLot push permanently failed', [
            'lots' => $this->payload['lots'] ?? [],
            'error' => $exception->getMessage(),
        ]);
    }
}
