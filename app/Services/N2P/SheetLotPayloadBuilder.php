<?php

namespace App\Services\N2P;

use App\Models\Products\StockMove;
use Illuminate\Support\Facades\Log;

class SheetLotPayloadBuilder
{
    /**
     * Codes d'unité acceptés pour push d'un lot tôle vers N2P.
     * L'unité doit être "pièce" — sinon la qté n'a pas de sens (m², kg...).
     * Conversion m²/kg → pièces = chantier V2.
     */
    private const SHEET_UNIT_CODES = ['PC', 'UN', 'U', 'UNIT', 'PIECE', 'PIÈCE', 'PCE'];

    public const EXTERNAL_REF_PREFIX = 'MV-';

    /**
     * Construit le payload pour POST /api/plugin/stock-lots — enveloppe {lots: [...]}.
     * Retourne null si le mouvement ne doit pas être poussé (mauvaise unité,
     * pas de produit rattaché, etc.). L'observer skippe alors le dispatch.
     */
    public function buildForMove(StockMove $move): ?array
    {
        $move->loadMissing([
            'StockLocationProducts.product.Unit',
            'StockLocationProducts.StockLocation',
            'purchaseReceiptLines.purchaseLines.purchase.companie',
        ]);

        $slp = $move->StockLocationProducts;
        $product = $slp?->product;

        if (! $product) {
            Log::channel('n2p')->warning('SheetLot skip: no product resolvable from stock move', [
                'stock_move_id' => $move->id,
            ]);
            return null;
        }

        $unitCode = strtoupper((string) ($product->Unit?->code ?? ''));
        if ($unitCode !== '' && ! in_array($unitCode, self::SHEET_UNIT_CODES, true)) {
            Log::channel('n2p')->warning('SheetLot skip: unit not in whitelist (chantier V2: conversion)', [
                'stock_move_id' => $move->id,
                'unit_code' => $unitCode,
                'qty' => $move->qty,
            ]);
            return null;
        }

        $receiptLine = $move->purchaseReceiptLines;
        $purchaseLine = $receiptLine?->purchaseLines ?? null;

        // Fournisseur : porté par la commande d'achat parente (purchase.companie),
        // pas par la ligne d'achat (purchase_lines.supplier_ref est un champ de
        // référence texte libre, souvent vide). On préfère le label métier ; à
        // défaut, le code interne.
        $supplierName = null;
        $supplier = $purchaseLine?->purchase?->companie;
        if ($supplier) {
            $supplierName = $this->stringOrNull($supplier->label) ?? $this->stringOrNull($supplier->code);
        }

        // Emplacement : StockLocation n'a pas de colonne `name` — c'est `label`
        // (nom humain "Rack pièces") ou `code` (identifiant "RACK2"). On envoie
        // "label (code)" pour que N2P affiche un nom compréhensible ET la ref.
        $locationName = null;
        $loc = $slp?->StockLocation;
        if ($loc) {
            $label = $this->stringOrNull($loc->label);
            $code  = $this->stringOrNull($loc->code);
            if ($label && $code && $label !== $code) {
                $locationName = "{$label} ({$code})";
            } else {
                $locationName = $label ?? $code;
            }
        }

        // Dimensions PAR LOT : stock_moves.x/y/z_size portent la vraie taille de
        // la tôle reçue (voir migration 2024_05_23_193135). On tombe sur la
        // fiche produit uniquement si le move n'a pas la dim (import legacy).
        $sheetX = $this->floatOrNull($move->x_size) ?? $this->floatOrNull($product->x_size);
        $sheetY = $this->floatOrNull($move->y_size) ?? $this->floatOrNull($product->y_size);
        $sheetZ = $this->floatOrNull($move->z_size) ?? $this->floatOrNull($product->thickness);

        // stock_moves.component_price a un DEFAULT 0 — le fallback ?? ne se
        // déclenche jamais. On tombe sur purchased_price dès que la valeur du
        // move est nulle ou 0 (pas de coût réel enregistré à la réception).
        $moveUnitCost = $this->floatOrNull($move->component_price);
        $unitCost = ($moveUnitCost !== null && $moveUnitCost > 0)
            ? $moveUnitCost
            : $this->floatOrNull($product->purchased_price);

        return [
            'lots' => [array_filter([
                'external_ref'  => self::EXTERNAL_REF_PREFIX . $move->id,
                'material'      => $this->stringOrNull($product->material),
                'finish'        => $this->stringOrNull($product->finishing),
                'thickness'     => $this->floatOrNull($product->thickness),
                'sheet_x'       => $sheetX,
                'sheet_y'       => $sheetY,
                'sheet_z'       => $sheetZ,
                'supplier'      => $supplierName,
                'received_at'   => optional($move->created_at)->toDateString(),
                'location'      => $locationName,
                'unit_weight'   => $this->floatOrNull($product->weight),
                'unit_cost'     => $unitCost,
                'initial_qty'   => (int) round((float) $move->qty),
                'lot_number'    => $this->stringOrNull($move->tracability),
                'ccpu_reference'=> null,
                'notes'         => null,
            ], static fn ($v) => $v !== null && $v !== '')],
        ];
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) return null;
        $s = trim((string) $value);
        return $s === '' ? null : $s;
    }

    private function floatOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '') return null;
        return (float) $value;
    }
}
