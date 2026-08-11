<?php

namespace App\Observers;

use App\Jobs\PushSheetLotToN2P;
use App\Models\Integrations\IntegrationEndpoint;
use App\Models\Products\StockLocationProducts;
use App\Models\Products\StockMove;
use App\Services\N2P\SheetLotPayloadBuilder;
use App\Services\StockReservationService;
use Illuminate\Support\Facades\Log;
use Throwable;

class StockMoveObserver
{
    /** Type de mouvement "réception d'achat" (voir migration stock_moves.typ_move). */
    private const TYP_MOVE_PO_RECEPTION = 3;

    /** Type d'un MethodsServices signifiant "matière première tôle". */
    private const SHEET_METAL_SERVICE_TYPE = 3;

    public function __construct(private readonly StockReservationService $reservations) {}

    public function created(StockMove $move): void
    {
        if (!$move->stock_location_products_id) {
            return;
        }

        // Le mouvement porte sur un emplacement produit précis : on remonte au
        // products_id pour recalculer la répartition sur toutes les tâches
        // qui consomment ce composant.
        $productId = StockLocationProducts::whereKey($move->stock_location_products_id)->value('products_id');

        if ($productId) {
            $this->reservations->recomputeForProduct((int) $productId);
        }

        $this->emitSheetLotToN2P($move);
    }

    /**
     * Si le mouvement est une réception de tôle matière première, dispatch un
     * push vers Nest2Prod. Silencieux si l'endpoint n'est pas configuré ou si
     * la construction du payload échoue.
     */
    private function emitSheetLotToN2P(StockMove $move): void
    {
        try {
            if ((int) $move->typ_move !== self::TYP_MOVE_PO_RECEPTION) {
                return;
            }

            $move->loadMissing('StockLocationProducts.product.family.service');
            $service = $move->StockLocationProducts?->product?->family?->service;

            if (! $service || (int) $service->type !== self::SHEET_METAL_SERVICE_TYPE) {
                return;
            }

            $endpoint = IntegrationEndpoint::query()
                ->forSystem('n2p')
                ->outbound()
                ->active()
                ->first();

            if (! $endpoint) {
                return;
            }

            $payload = app(SheetLotPayloadBuilder::class)->buildForMove($move);
            if ($payload === null) {
                return;
            }

            PushSheetLotToN2P::dispatch($payload);
        } catch (Throwable $e) {
            // On ne veut PAS que l'échec d'un push N2P casse la création d'un
            // mouvement de stock (opération métier critique).
            Log::channel('n2p')->error('SheetLot dispatch skipped due to exception', [
                'stock_move_id' => $move->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
