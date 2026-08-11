<?php

namespace App\Console\Commands;

use App\Jobs\PushSheetLotToN2P;
use App\Models\Integrations\IntegrationEndpoint;
use App\Models\Products\StockMove;
use App\Services\N2P\SheetLotPayloadBuilder;
use Illuminate\Console\Command;

class SyncSheetStockCommand extends Command
{
    protected $signature = 'wem:n2p:sync-sheet-stock
                            {--days=30 : Fenêtre de rejeu en jours}
                            {--sync : Exécute immédiatement sans passer par la queue}';

    protected $description = 'Rejoue les réceptions de tôles matière première vers Nest2Prod (rattrapage / setup initial).';

    public function handle(SheetLotPayloadBuilder $builder): int
    {
        $endpoint = IntegrationEndpoint::query()
            ->forSystem('n2p')
            ->outbound()
            ->active()
            ->first();

        if (! $endpoint) {
            $this->error('Endpoint N2P sortant absent ou désactivé. Rien à faire.');
            return self::FAILURE;
        }

        $days = max(1, (int) $this->option('days'));
        $sync = (bool) $this->option('sync');
        $since = now()->subDays($days);

        $query = StockMove::query()
            ->where('typ_move', 3)
            ->where('created_at', '>=', $since)
            ->whereHas('StockLocationProducts.product.family.service', fn ($q) => $q->where('type', 3))
            ->with('StockLocationProducts.product.family.service', 'StockLocationProducts.StockLocation', 'purchaseReceiptLines.purchaseLines')
            ->orderBy('id');

        $total = $query->count();
        $this->info("Fenêtre : {$days} j → {$total} mouvement(s) tôle candidat(s).");

        $dispatched = 0;
        $skipped = 0;

        $query->chunkById(200, function ($moves) use ($builder, $sync, &$dispatched, &$skipped) {
            foreach ($moves as $move) {
                $payload = $builder->buildForMove($move);
                if ($payload === null) {
                    $skipped++;
                    continue;
                }

                if ($sync) {
                    (new PushSheetLotToN2P($payload))->handle();
                } else {
                    PushSheetLotToN2P::dispatch($payload);
                }

                $dispatched++;
            }
        });

        $mode = $sync ? 'exécutés' : 'mis en queue';
        $this->info("Lots {$mode} : {$dispatched} · ignorés (unité invalide / produit manquant) : {$skipped}");

        return self::SUCCESS;
    }
}
