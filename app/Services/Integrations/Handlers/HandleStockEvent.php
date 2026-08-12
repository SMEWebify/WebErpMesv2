<?php

namespace App\Services\Integrations\Handlers;

use App\Models\Integrations\IntegrationEndpoint;
use App\Models\Products\StockLocationProducts;
use App\Models\Products\StockMove;
use App\Models\User;
use App\Services\Integrations\IntegrationEventHandler;
use App\Services\StockService;
use App\Services\N2P\SheetLotPayloadBuilder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class HandleStockEvent implements IntegrationEventHandler
{
    public const EVENT_CONSUMED = 'stock.consumed';

    /** typ_move=2 = Task allocation / consumption (voir migration stock_moves). */
    private const TYP_MOVE_CONSUMPTION = 2;

    public function __construct(private StockService $stockService)
    {
    }

    public function handle(IntegrationEndpoint $endpoint, array $data, array $meta): void
    {
        $eventType = (string) ($meta['event_type'] ?? '');
        if ($eventType !== self::EVENT_CONSUMED) {
            throw new InvalidArgumentException("Unsupported stock event: {$eventType}");
        }

        $externalRef = (string) ($data['stock_lot_external_ref'] ?? '');
        $qty = (int) ($data['qty'] ?? 0);

        if ($externalRef === '' || $qty <= 0) {
            throw new InvalidArgumentException('stock.consumed requires stock_lot_external_ref and positive qty');
        }

        $originalMove = $this->resolveOriginalMove($externalRef);
        if (! $originalMove) {
            throw new RuntimeException("Original StockMove not found for external_ref: {$externalRef}");
        }

        $slpId = $originalMove->stock_location_products_id;
        if (! $slpId) {
            throw new RuntimeException("Original StockMove #{$originalMove->id} has no SLP");
        }

        $consumedBy = $data['consumed_by'] ?? [];
        $tracability = $this->formatTracability($originalMove->tracability, $consumedBy);
        $userId = $this->resolveUserId($data, $originalMove);

        // Lock la SLP puis vérifie que la conso ne rendra pas le stock négatif.
        // Aligne le comportement sur TaskStatuController@goodQtyStock (FIFO id).
        DB::transaction(function () use ($slpId, $qty, $originalMove, $tracability, $userId) {
            $slp = StockLocationProducts::query()
                ->whereKey($slpId)
                ->lockForUpdate()
                ->first();

            if (! $slp) {
                throw new RuntimeException("SLP #{$slpId} disappeared during transaction");
            }

            $available = (int) $slp->getCurrentStockMove();
            if ($available < $qty) {
                throw new RuntimeException(
                    "Insufficient stock on SLP #{$slpId}: available={$available}, requested={$qty}"
                );
            }

            $this->stockService->createStockMove([
                'user_id'                    => $userId,
                'stock_location_products_id' => $slpId,
                'typ_move'                   => self::TYP_MOVE_CONSUMPTION,
                'qty'                        => $qty,
                'batch_id'                   => $originalMove->batch_id,
                'tracability'                => $tracability,
            ]);
        });
    }

    /**
     * Résolution user_id pour un mouvement piloté par un système externe :
     *  1. user_id fourni dans l'event (opérateur N2P mappé)
     *  2. user_id systeme configuré dans integrations.system_user_id
     *  3. user_id du mouvement d'origine (celui qui a reçu la tôle)
     *  4. premier utilisateur en base — last-resort pour ne pas bloquer.
     */
    private function resolveUserId(array $data, StockMove $originalMove): int
    {
        if (isset($data['user_id']) && is_numeric($data['user_id'])) {
            $candidate = (int) $data['user_id'];
            if ($candidate > 0 && User::query()->whereKey($candidate)->exists()) {
                return $candidate;
            }
        }

        $configured = (int) config('integrations.system_user_id', 0);
        if ($configured > 0 && User::query()->whereKey($configured)->exists()) {
            return $configured;
        }

        if ($originalMove->user_id) {
            return (int) $originalMove->user_id;
        }

        $fallback = (int) User::query()->value('id');
        if ($fallback === 0) {
            throw new RuntimeException('No user available to attribute the stock consumption');
        }

        return $fallback;
    }

    /**
     * L'external_ref est "MV-<id>" (voir SheetLotPayloadBuilder::EXTERNAL_REF_PREFIX).
     */
    private function resolveOriginalMove(string $externalRef): ?StockMove
    {
        $prefix = SheetLotPayloadBuilder::EXTERNAL_REF_PREFIX;
        if (! str_starts_with($externalRef, $prefix)) {
            return null;
        }

        $id = (int) substr($externalRef, strlen($prefix));
        if ($id <= 0) {
            return null;
        }

        return StockMove::query()->whereKey($id)->first();
    }

    private function formatTracability(?string $original, array $consumedBy): string
    {
        $bits = [];
        if ($original !== null && $original !== '') {
            $bits[] = $original;
        }

        $nestName = $consumedBy['nest_name'] ?? null;
        $nestId = $consumedBy['nest_id'] ?? null;
        if ($nestName || $nestId) {
            $bits[] = 'N2P nest ' . ($nestName ?: '#' . $nestId);
        }

        return implode(' | ', $bits);
    }
}
