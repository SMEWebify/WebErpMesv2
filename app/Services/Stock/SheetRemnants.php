<?php

namespace App\Services\Stock;

use App\Models\Products\Batch;
use App\Models\Products\StockLocationProducts;
use App\Models\Products\StockMove;
use App\Services\StockService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Pièces physiques d'une ligne de stock (tôle entière, chute) et remise en stock
 * des chutes.
 *
 * Une pièce = les mouvements de même lot et de même géométrie, la clé de
 * l'inventaire (InventoryService::snapshotDetails). Rien n'est stocké en plus :
 * le solde d'une pièce se recalcule depuis stock_moves.
 *
 * Les sorties écrites avant cette fonctionnalité ne portent pas de géométrie :
 * leur solde négatif est imputé d'abord aux pièces au format de l'article, puis
 * aux chutes, pour que la somme des pièces reste le stock de la ligne.
 */
class SheetRemnants
{
    /** Entrée d'une chute : réception manuelle, marquée par remnant_source_move_id. */
    public const ENTRY_TYPE = 5;

    public const MAX_REMNANTS = 4;

    private const INCOMING = [1, 3, 5, 12, 14];
    private const OUTGOING = [2, 4, 6, 9, 15];

    /** Colonnes qui identifient une pièce (même clé que l'inventaire). */
    private const PIECE_COLUMNS = ['batch_id', 'x_size', 'y_size', 'z_size', 'nb_part', 'surface_perc'];

    private const EPSILON = 0.0001;

    public function __construct(private readonly StockService $stockService)
    {
    }

    /**
     * Pièces en stock d'une ligne, quantité positive seulement : pièces au format
     * de l'article d'abord, puis chutes de la plus grande à la plus petite.
     *
     * @return array<int, array<string, mixed>>
     */
    public function pieces(StockLocationProducts $slp): array
    {
        $pieces = $this->settle($this->balances(slpIds: [$slp->id]))
            ->filter(fn ($p) => $p['qty'] > self::EPSILON)
            ->sortBy([
                fn ($a, $b) => $a['is_remnant'] <=> $b['is_remnant'],
                fn ($a, $b) => $this->area($b) <=> $this->area($a),
                fn ($a, $b) => $a['first_id'] <=> $b['first_id'],
            ])
            ->values();

        $batches = Batch::whereIn('id', $pieces->pluck('batch_id')->filter()->unique())
            ->get(['id', 'code', 'heat_number'])
            ->keyBy('id');

        return $pieces->map(function ($p) use ($batches) {
            $batch = $p['batch_id'] ? $batches->get($p['batch_id']) : null;

            return $p + [
                'batch_code'  => $batch?->code,
                'heat_number' => $batch?->heat_number,
                'format'      => $this->format($p['x_size'], $p['y_size'], $p['z_size']),
            ];
        })->all();
    }

    /**
     * Nombre de chutes en stock par article, toutes lignes de stock confondues.
     * Une requête.
     *
     * @param  array<int, int>  $productIds
     * @return array<int, float>
     */
    public function remnantQtyByProduct(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        $out = [];
        foreach ($this->settle($this->balances(productIds: $productIds)) as $piece) {
            if ($piece['is_remnant'] && $piece['qty'] > self::EPSILON) {
                $out[$piece['products_id']] = ($out[$piece['products_id']] ?? 0) + $piece['qty'];
            }
        }

        return $out;
    }

    /**
     * Sortie d'une pièce donnée, puis remise en stock de ses chutes.
     *
     * @param  array<string, mixed>  $context  user_id, typ_move, order_line_id, task_id
     * @param  array<int, array{x_size: float, y_size: float, qty: int}>  $remnants  déjà normalisées
     * @return array{exit: StockMove, remnants: array<int, StockMove>}
     *
     * @throws \DomainException pièce introuvable, quantité insuffisante, chute plus grande que la pièce
     */
    public function dispatchPiece(StockLocationProducts $slp, string $pieceKey, float $qty, array $remnants, array $context): array
    {
        return DB::transaction(function () use ($slp, $pieceKey, $qty, $remnants, $context) {
            $slp = StockLocationProducts::whereKey($slp->id)->lockForUpdate()->firstOrFail();

            $piece = collect($this->pieces($slp))->firstWhere('key', $pieceKey);
            if (! $piece) {
                throw new \DomainException(__('stock_remnants.piece_not_found'));
            }
            if ($piece['qty'] + self::EPSILON < $qty) {
                throw new \DomainException(__('stock_remnants.not_enough', ['qty' => $this->number($piece['qty'])]));
            }

            $data = [
                'user_id'                    => $context['user_id'],
                'qty'                        => $qty,
                'stock_location_products_id' => $slp->id,
                'typ_move'                   => $context['typ_move'],
                'order_line_id'              => $context['order_line_id'] ?? null,
                'task_id'                    => $context['task_id'] ?? null,
                'tracability'                => $piece['tracability'],
            ] + array_intersect_key($piece, array_flip(self::PIECE_COLUMNS));
            // Valeur de la sortie : seulement quand l'appelant la veut (imbrication) ;
            // colonne non nullable, 0 par défaut comme les autres sorties.
            if (! empty($context['component_price'])) {
                $data['component_price'] = $context['component_price'];
            }
            $exit = $this->stockService->createStockMove($data);

            if (! empty($context['nest_id'])) {
                $exit->forceFill(['nest_id' => $context['nest_id']])->save();
            }

            return [
                'exit'     => $exit,
                'remnants' => $this->putBack($exit, $remnants, $qty, $piece['x_size'], $piece['y_size'], $piece['z_size']),
            ];
        });
    }

    /**
     * Remet en stock les chutes d'une sortie, sur la même ligne de stock, avec le
     * lot et la traçabilité de la pièce. Valeur au prorata de la surface du coût
     * moyen de la ligne : la valeur du stock reste celle de la matière restante.
     *
     * Dimensions de la pièce inconnues → celles de l'article ; inconnues aussi →
     * chute acceptée sans contrôle de taille, sans valeur (CUMP non touché).
     *
     * @param  array<int, array{x_size: float, y_size: float, qty: int}>  $remnants
     * @return array<int, StockMove>
     */
    public function putBack(StockMove $exit, array $remnants, float $piecesQty, ?float $pieceX, ?float $pieceY, ?float $pieceZ): array
    {
        if ($remnants === []) {
            return [];
        }

        $slp = $exit->StockLocationProducts()->with('Product')->firstOrFail();
        $product = $slp->Product;

        if (! ($pieceX > 0 && $pieceY > 0)) {
            $pieceX = $product?->x_size > 0 ? (float) $product->x_size : null;
            $pieceY = $product?->y_size > 0 ? (float) $product->y_size : null;
        }
        $pieceZ ??= ($product?->thickness > 0 ? (float) $product->thickness : ($product?->z_size > 0 ? (float) $product->z_size : null));

        $pieceArea = ($pieceX && $pieceY) ? $pieceX * $pieceY : null;

        if ($pieceArea !== null) {
            $total = 0.0;
            foreach ($remnants as $r) {
                if (! $this->fits($r['x_size'], $r['y_size'], $pieceX, $pieceY)) {
                    throw new \DomainException(__('stock_remnants.too_large', [
                        'remnant' => $this->format($r['x_size'], $r['y_size'], null),
                        'piece'   => $this->format($pieceX, $pieceY, null),
                    ]));
                }
                $total += $r['x_size'] * $r['y_size'] * $r['qty'];
            }
            if ($total >= $pieceArea * $piecesQty - self::EPSILON) {
                throw new \DomainException(__('stock_remnants.area_exceeded'));
            }
        }

        $unitCost = (float) $slp->unit_cost;
        $moves = [];

        foreach ($remnants as $r) {
            $data = [
                'user_id'                    => $exit->user_id,
                'qty'                        => $r['qty'],
                'stock_location_products_id' => $slp->id,
                'typ_move'                   => self::ENTRY_TYPE,
                'task_id'                    => $exit->task_id,
                'batch_id'                   => $exit->batch_id,
                'tracability'                => $exit->tracability,
                'x_size'                     => $r['x_size'],
                'y_size'                     => $r['y_size'],
                'z_size'                     => $pieceZ,
            ];

            if ($pieceArea !== null && $unitCost > 0) {
                $data['component_price'] = round($unitCost * ($r['x_size'] * $r['y_size']) / $pieceArea, 3);
                $move = $this->stockService->createStockMove($data);
            } else {
                // Sans valeur connue, ne pas tirer le CUMP vers zéro.
                $move = StockMove::create($data);
            }

            $move->forceFill(['remnant_source_move_id' => $exit->id, 'nest_id' => $exit->nest_id])->save();
            $moves[] = $move;
        }

        return $moves;
    }

    /**
     * Lignes saisies → chutes à créer. Lignes vides ignorées.
     *
     * @param  mixed  $input
     * @return array<int, array{x_size: float, y_size: float, qty: int}>
     *
     * @throws \DomainException ligne à moitié remplie ou valeur non positive
     */
    public function normalize($input): array
    {
        if (! is_array($input)) {
            return [];
        }

        $out = [];
        foreach ($input as $row) {
            if (! is_array($row)) {
                continue;
            }
            $x = $row['x_size'] ?? null;
            $y = $row['y_size'] ?? null;
            if (($x === null || $x === '') && ($y === null || $y === '')) {
                continue;
            }
            $q = ($row['qty'] ?? '') === '' ? 1 : $row['qty'];
            if (! is_numeric($x) || ! is_numeric($y) || (float) $x <= 0 || (float) $y <= 0
                || ! is_numeric($q) || (int) $q != $q || (int) $q < 1) {
                throw new \DomainException(__('stock_remnants.invalid_dimensions'));
            }
            $out[] = ['x_size' => (float) $x, 'y_size' => (float) $y, 'qty' => (int) $q];
        }

        if (count($out) > self::MAX_REMNANTS) {
            throw new \DomainException(__('stock_remnants.too_many', ['max' => self::MAX_REMNANTS]));
        }

        return $out;
    }

    /**
     * Soldes par (ligne de stock, pièce), avec le format de l'article.
     *
     * @param  array<int, int>|null  $slpIds
     * @param  array<int, int>|null  $productIds
     */
    private function balances(?array $slpIds = null, ?array $productIds = null): Collection
    {
        $in  = implode(',', self::INCOMING);
        $out = implode(',', self::OUTGOING);

        return DB::table('stock_moves as sm')
            ->join('stock_location_products as slp', 'slp.id', '=', 'sm.stock_location_products_id')
            ->join('products as p', 'p.id', '=', 'slp.products_id')
            ->when($slpIds !== null, fn ($q) => $q->whereIn('slp.id', $slpIds))
            ->when($productIds !== null, fn ($q) => $q->whereIn('slp.products_id', $productIds))
            ->groupBy('slp.id', 'slp.products_id', 'p.x_size', 'p.y_size',
                'sm.batch_id', 'sm.x_size', 'sm.y_size', 'sm.z_size', 'sm.nb_part', 'sm.surface_perc')
            ->selectRaw("
                slp.id          AS slp_id,
                slp.products_id AS products_id,
                p.x_size        AS product_x,
                p.y_size        AS product_y,
                sm.batch_id     AS batch_id,
                sm.x_size       AS x_size,
                sm.y_size       AS y_size,
                sm.z_size       AS z_size,
                sm.nb_part      AS nb_part,
                sm.surface_perc AS surface_perc,
                MIN(sm.id)          AS first_id,
                MAX(sm.tracability) AS tracability,
                MAX(CASE WHEN sm.remnant_source_move_id IS NOT NULL THEN 1 ELSE 0 END) AS remnant_mark,
                COALESCE(SUM(CASE
                    WHEN sm.typ_move IN ($in)  THEN sm.qty
                    WHEN sm.typ_move IN ($out) THEN -sm.qty
                    ELSE 0
                END), 0) AS qty
            ")
            ->get();
    }

    /**
     * Normalise les soldes et impute les soldes négatifs (sorties sans géométrie)
     * aux autres pièces de la même ligne de stock.
     */
    private function settle(Collection $rows): Collection
    {
        return $rows->groupBy('slp_id')->flatMap(function (Collection $group) {
            $pieces = $group->map(function ($r) {
                $piece = [
                    'slp_id'       => (int) $r->slp_id,
                    'products_id'  => (int) $r->products_id,
                    'batch_id'     => $r->batch_id !== null ? (int) $r->batch_id : null,
                    'x_size'       => $this->num($r->x_size),
                    'y_size'       => $this->num($r->y_size),
                    'z_size'       => $this->num($r->z_size),
                    'nb_part'      => $r->nb_part !== null ? (int) $r->nb_part : null,
                    'surface_perc' => $this->num($r->surface_perc),
                    'tracability'  => $r->tracability,
                    'first_id'     => (int) $r->first_id,
                    'qty'          => (float) $r->qty,
                ];
                $piece['is_remnant'] = (bool) $r->remnant_mark
                    || ($piece['x_size'] > 0 && $piece['y_size'] > 0 && $r->product_x > 0 && $r->product_y > 0
                        && ! $this->sameFormat($piece['x_size'], $piece['y_size'], (float) $r->product_x, (float) $r->product_y));
                $piece['key'] = $this->key($piece);

                return $piece;
            })->values()->all();

            $deficit = 0.0;
            foreach ($pieces as $i => $p) {
                if ($p['qty'] < 0) {
                    $deficit -= $p['qty'];
                    $pieces[$i]['qty'] = 0.0;
                }
            }

            if ($deficit > self::EPSILON) {
                $order = collect($pieces)->keys()->sortBy([
                    fn ($a, $b) => $pieces[$a]['is_remnant'] <=> $pieces[$b]['is_remnant'],
                    fn ($a, $b) => $pieces[$a]['first_id'] <=> $pieces[$b]['first_id'],
                ]);
                foreach ($order as $i) {
                    $take = min($pieces[$i]['qty'], $deficit);
                    $pieces[$i]['qty'] -= $take;
                    $deficit -= $take;
                    if ($deficit <= self::EPSILON) {
                        break;
                    }
                }
            }

            return $pieces;
        })->values();
    }

    private function key(array $piece): string
    {
        return implode('|', array_map(
            fn ($col) => $piece[$col] === null ? '' : $this->number((float) $piece[$col], 8),
            self::PIECE_COLUMNS,
        ));
    }

    private function fits(float $x, float $y, float $px, float $py): bool
    {
        return max($x, $y) <= max($px, $py) + self::EPSILON
            && min($x, $y) <= min($px, $py) + self::EPSILON;
    }

    private function sameFormat(float $x, float $y, float $px, float $py): bool
    {
        return abs(max($x, $y) - max($px, $py)) < self::EPSILON
            && abs(min($x, $y) - min($px, $py)) < self::EPSILON;
    }

    private function area(array $piece): float
    {
        return ($piece['x_size'] ?? 0) * ($piece['y_size'] ?? 0);
    }

    private function num($value): ?float
    {
        return $value === null ? null : (float) $value;
    }

    private function number(float $value, int $decimals = 3): string
    {
        $s = number_format($value, $decimals, '.', '');

        return str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
    }

    private function format(?float $x, ?float $y, ?float $z): ?string
    {
        if (! ($x > 0 && $y > 0)) {
            return null;
        }

        $parts = [$this->number($x), $this->number($y)];
        if ($z > 0) {
            $parts[] = $this->number($z);
        }

        return implode(' × ', $parts);
    }
}
