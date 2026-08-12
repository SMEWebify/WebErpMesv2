<?php

namespace App\Http\Controllers\Api\N2P;

use App\Http\Controllers\Controller;
use App\Models\Products\StockMove;
use App\Services\N2P\SheetLotPayloadBuilder;
use Illuminate\Http\JsonResponse;

class SheetLotStockController extends Controller
{
    /** typ_move d'origine autorisés — on n'expose PAS n'importe quel mouvement. */
    private const ALLOWED_ORIGIN_TYP_MOVES = [3, 5, 12, 14]; // PO reception, manual, manufactured, transfer in

    /** MethodsServices.type = 3 → matière première tôle. */
    private const SHEET_METAL_SERVICE_TYPE = 3;

    /**
     * Retourne l'état stock ERP d'un lot tôle référencé par son external_ref
     * (ex : "MV-42"). Consulté par N2P pour rattraper un désync éventuel du
     * mirror local (sheet_stocks) sans attendre le prochain push ERP → N2P.
     */
    public function show(string $ref): JsonResponse
    {
        $prefix = SheetLotPayloadBuilder::EXTERNAL_REF_PREFIX;
        if (! str_starts_with($ref, $prefix)) {
            return response()->json(['error' => 'invalid_ref_format'], 400);
        }

        $moveId = (int) substr($ref, strlen($prefix));
        if ($moveId <= 0) {
            return response()->json(['error' => 'invalid_ref_id'], 400);
        }

        $move = StockMove::query()
            ->with([
                'StockLocationProducts.product:id,code,label,material,thickness,x_size,y_size,products_families_id',
                'StockLocationProducts.product.family.service',
                'StockLocationProducts.StockLocation:id,name',
            ])
            ->whereKey($moveId)
            ->first();

        if (! $move) {
            return response()->json(['error' => 'ref_not_found'], 404);
        }

        // Refuse tout ref pointant sur un mouvement qui n'est pas une entrée
        // de tôle : sinon un attaquant avec un token Sanctum pourrait
        // itérer MV-1..N et lire n'importe quel stock_move (livraison client,
        // dispatch manuel, produits non-tôle...).
        if (! in_array((int) $move->typ_move, self::ALLOWED_ORIGIN_TYP_MOVES, true)) {
            return response()->json(['error' => 'ref_not_a_sheet_lot'], 404);
        }

        $slp = $move->StockLocationProducts;
        if (! $slp) {
            return response()->json(['error' => 'no_slp_attached'], 404);
        }

        $product = $slp->product;
        $service = $product?->family?->service;
        if (! $service || (int) $service->type !== self::SHEET_METAL_SERVICE_TYPE) {
            return response()->json(['error' => 'ref_not_a_sheet_lot'], 404);
        }

        // Attention : getCurrentStockMove() renvoie le STOCK AGREGE de la SLP,
        // tous lots confondus — pas la qty restante du lot MV-{id} exact.
        // On documente le champ (aggregate_qty) pour ne pas induire N2P en
        // erreur. Le suivi par lot fin nécessite une table dédiée (chantier V2).
        $aggregateQty = (float) $slp->getCurrentStockMove();

        return response()->json([
            'external_ref'  => $ref,
            'aggregate_qty' => $aggregateQty,
            'initial_qty'   => (int) $move->qty,
            'received_at'   => optional($move->created_at)->toIso8601String(),
            'product'       => $product ? [
                'code'      => $product->code,
                'label'     => $product->label,
                'material'  => $product->material,
                'thickness' => $product->thickness,
                'sheet_x'   => $move->x_size ?? $product->x_size,
                'sheet_y'   => $move->y_size ?? $product->y_size,
                'sheet_z'   => $move->z_size ?? $product->thickness,
            ] : null,
            'location'      => $slp->StockLocation?->name,
        ]);
    }
}
