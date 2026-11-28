<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Products\Nest;
use App\Models\Products\StockMove;
use App\Services\Stock\NestRecorder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Imbrication réalisée envoyée par une FAO tierce : tôle, pièces par ligne de
 * commande, chutes. Identifiée par (`system`, `external_id`), rejouable. La tôle
 * est sortie du stock et les chutes y sont remises une seule fois, au statut
 * `done` (NestRecorder::upsertFromImport).
 *
 * Jeton Sanctum ; un jeton restreint doit porter la capacité « nests:write ». Même
 * droit que l'écran Imbrications (`stock-lot-serial-management`). 201 à la
 * création, 200 sinon ; 422 si la tôle ne peut pas être sortie (rien n'est écrit).
 */
class InboundNestController extends Controller
{
    public function store(Request $request, NestRecorder $recorder)
    {
        abort_unless($request->user()?->tokenCan('nests:write'), 403);
        abort_unless($request->user()->can('stock-lot-serial-management'), 403);

        $validated = $request->validate([
            'system'               => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_-]+$/'],
            'external_id'          => 'required|string|max:190',
            'name'                 => 'nullable|string|max:255',
            'status'               => ['nullable', Rule::in([Nest::STATUS_PLANNED, Nest::STATUS_IN_PROGRESS, Nest::STATUS_DONE, Nest::STATUS_CANCELLED])],
            'cut_at'               => 'nullable|date',
            'machine'              => 'nullable|string|max:255',
            'material'             => 'nullable|string|max:255',
            'usage_percent'        => 'nullable|numeric|min:0|max:100',
            'comment'              => 'nullable|string|max:5000',
            'consume_stock'        => 'nullable|boolean',
            'sheet_qty'            => 'required|numeric|gt:0|max:10000',
            'sheet'                => 'required|array',
            'sheet.product_code'   => 'required_without:sheet.product_id|nullable|string|max:255',
            'sheet.product_id'     => 'nullable|integer',
            'sheet.stock_line_code' => 'nullable|string|max:255',
            'sheet.batch'          => 'nullable|string|max:255',
            'sheet.x_size'         => 'nullable|numeric|gt:0',
            'sheet.y_size'         => 'nullable|numeric|gt:0',
            'sheet.z_size'         => 'nullable|numeric|gt:0',
            'parts'                => 'nullable|array|max:500',
            'parts.*.order_line_id' => 'nullable|integer',
            'parts.*.of_code'      => 'nullable|string|max:50',
            'parts.*.part_ref'     => 'nullable|string|max:255',
            'parts.*.external_ref' => 'nullable|string|max:255',
            'parts.*.qty'          => 'required|numeric|gt:0',
            'parts.*.good_qty'     => 'nullable|numeric|min:0',
            'parts.*.bad_qty'      => 'nullable|numeric|min:0',
            'parts.*.area_mm2'     => 'nullable|numeric|gt:0',
            'parts.*.weight_kg'    => 'nullable|numeric|gt:0',
            'remnants'             => 'nullable|array|max:4',
            'remnants.*.width'     => 'required|numeric|gt:0',
            'remnants.*.height'    => 'required|numeric|gt:0',
            'remnants.*.quantity'  => 'nullable|integer|min:1|max:100',
        ]);

        try {
            $result = $recorder->upsertFromImport($validated['system'], $validated, $request->user());
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => ['sheet' => [$e->getMessage()]]], 422);
        }

        $nest = $result['nest'];
        $exit = $nest->stockMoves()->where('typ_move', 2)->orderBy('id')->first();

        return response()->json([
            'id'          => $nest->id,
            'external_id' => $validated['external_id'],
            'name'        => $nest->name,
            'status'      => $nest->status,
            'created'     => $result['created'],
            'stock'       => [
                'recorded'      => $exit !== null,
                'recorded_now'  => $result['stock_recorded_now'],
                'stock_line_id' => $exit?->stock_location_products_id,
                'sheet_qty'     => $exit ? (float) $exit->qty : null,
                'remnants'      => $nest->remnants_recorded_at !== null
                    ? (int) StockMove::whereIn('remnant_source_move_id', $nest->stockMoves()->select('id'))->count()
                    : 0,
            ],
            'parts'       => $result['parts'],
            'warnings'    => $result['warnings'],
            'url'         => route('products.nests.show', ['nest' => $nest->id]),
        ], $result['created'] ? 201 : 200);
    }
}
