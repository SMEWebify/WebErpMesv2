<?php

namespace App\Services\Stock;

use App\Models\Planning\Task;
use App\Models\Products\Nest;
use App\Models\Products\NestPart;
use App\Models\Products\Products;
use App\Models\Products\StockLocationProducts;
use App\Models\Products\StockMove;
use App\Models\User;
use App\Models\Workflow\OrderLines;
use App\Services\N2P\SheetLotPayloadBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Imbrications réellement découpées : une sortie de tôle (ou de chute) répartie sur
 * les lignes de commande de ses pièces, et les chutes qu'elle rend au stock.
 *
 * Trois portes, un seul modèle : saisie à la main (écran Imbrications), message
 * Nest2Prod MES (`nest.updated` + `stock.consumed`), FAO tierce par l'API
 * (`POST /api/nests`, upsertFromImport). Les estimations NestEngine n'en sont jamais : elles ne disent pas ce
 * qui sera découpé.
 */
class NestRecorder
{
    /** Sortie de matière pour la production. */
    private const EXIT_TYPE = 2;

    private const OUTGOING = [2, 4, 6, 9, 15];

    /** Statuts de nests.statu de Nest2Prod MES. */
    private const N2P_STATUS = [
        1 => Nest::STATUS_PLANNED,
        2 => Nest::STATUS_PLANNED,
        3 => Nest::STATUS_IN_PROGRESS,
        4 => Nest::STATUS_PLANNED,
        5 => Nest::STATUS_PLANNED,
        6 => Nest::STATUS_DONE,
        7 => Nest::STATUS_CANCELLED,
    ];

    public function __construct(private readonly SheetRemnants $remnants)
    {
    }

    /**
     * Tâches qui peuvent recevoir des pièces d'une tôle : composant = l'article tôle,
     * rattachées à une ligne de commande, pas terminées.
     */
    public function candidateTasks(Products $product): Collection
    {
        return Task::with(['OrderLines.order', 'OrderLines.OrderLineDetails', 'status'])
            ->where('component_id', $product->id)
            ->whereNotNull('order_lines_id')
            ->whereDoesntHave('status', fn ($q) => $q->where('title', 'Finished'))
            ->orderByDesc('id')
            ->limit(100)
            ->get();
    }

    /**
     * Imbrication saisie à la main : sortie de la pièce choisie, pièces réparties
     * sur les tâches, chutes remises en stock.
     *
     * @param  array<int, array{task_id: int, qty: float}>  $parts
     * @param  array<int, array{x_size: float, y_size: float, qty: int}>  $remnants  normalisées
     * @param  array{name: string, usage_percent?: float|null, comment?: string|null}  $meta
     *
     * @throws \DomainException
     */
    public function recordManual(StockLocationProducts $slp, string $pieceKey, float $sheetQty, array $parts, array $remnants, array $meta, User $user): Nest
    {
        $parts = array_values(array_filter($parts, fn ($p) => ($p['qty'] ?? 0) > 0));
        if ($parts === []) {
            throw new \DomainException(__('nests.no_parts'));
        }

        $tasks = Task::with('OrderLines.OrderLineDetails')
            ->whereIn('id', array_column($parts, 'task_id'))
            ->where('component_id', $slp->products_id)
            ->whereNotNull('order_lines_id')
            ->get()
            ->keyBy('id');
        if ($tasks->count() !== count(array_unique(array_column($parts, 'task_id')))) {
            throw new \DomainException(__('nests.task_mismatch'));
        }

        $product = $slp->Product;
        $piece = collect($this->remnants->pieces($slp))->firstWhere('key', $pieceKey);
        if (! $piece) {
            throw new \DomainException(__('stock_remnants.piece_not_found'));
        }

        return DB::transaction(function () use ($slp, $pieceKey, $sheetQty, $parts, $remnants, $meta, $user, $tasks, $product, $piece) {
            $nest = Nest::create([
                'source'        => Nest::SOURCE_MANUAL,
                'name'          => $meta['name'],
                'product_id'    => $product?->id,
                'material'      => $product?->material,
                'thickness'     => $piece['z_size'] ?? $product?->thickness,
                'sheet_x'       => $piece['x_size'] ?? $product?->x_size,
                'sheet_y'       => $piece['y_size'] ?? $product?->y_size,
                'sheet_qty'     => $sheetQty,
                'usage_percent' => $meta['usage_percent'] ?? null,
                'status'        => Nest::STATUS_DONE,
                'cut_at'        => now(),
                'user_id'       => $user->id,
                'comment'       => $meta['comment'] ?? null,
            ]);

            $this->remnants->dispatchPiece($slp, $pieceKey, $sheetQty, $remnants, [
                'user_id'         => $user->id,
                'typ_move'        => self::EXIT_TYPE,
                // Une seule tâche servie : la sortie lui est aussi rattachée (fiche tâche).
                'task_id'         => count($parts) === 1 ? (int) $parts[0]['task_id'] : null,
                'nest_id'         => $nest->id,
                'component_price' => (float) $slp->unit_cost,
            ]);

            foreach ($parts as $part) {
                $task = $tasks[(int) $part['task_id']];
                $details = $task->OrderLines?->OrderLineDetails;
                $nest->parts()->create([
                    'task_id'       => $task->id,
                    'order_line_id' => $task->order_lines_id,
                    'part_ref'      => $task->OrderLines?->code ?? $task->OrderLines?->label,
                    'qty'           => (float) $part['qty'],
                    'good_qty'      => (float) $part['qty'],
                    'area_mm2'      => ($details?->x_size > 0 && $details?->y_size > 0) ? $details->x_size * $details->y_size : null,
                    'weight_kg'     => $details?->weight > 0 ? (float) $details->weight : null,
                ]);
            }

            if ($remnants !== []) {
                $nest->forceFill(['remnants_recorded_at' => now()])->save();
            }

            return $nest;
        });
    }

    /**
     * Message `nest.updated` de Nest2Prod MES : état complet de l'imbrication,
     * rejouable (les pièces sont remplacées, les chutes ne sont écrites qu'une fois).
     * La consommation de tôle arrive séparément par `stock.consumed`.
     *
     * @throws \InvalidArgumentException  identifiant absent
     */
    public function upsertFromN2P(array $data): Nest
    {
        $externalId = (string) ($data['nest_id'] ?? '');
        if ($externalId === '') {
            throw new \InvalidArgumentException('nest.updated requires nest_id');
        }

        return DB::transaction(function () use ($data, $externalId) {
            $nest = Nest::where('source', Nest::SOURCE_N2P)->where('external_id', $externalId)->lockForUpdate()->first()
                ?? new Nest(['source' => Nest::SOURCE_N2P, 'external_id' => $externalId]);

            $product = $this->productFromLots($data['stock_lots'] ?? []);
            $done = $data['sheet_qty_done'] ?? null;

            $nest->fill([
                'name'          => (string) ($data['nest_name'] ?? $nest->name ?? ('N2P #' . $externalId)),
                'product_id'    => $product?->id ?? $nest->product_id,
                'material'      => $data['material'] ?? $nest->material ?? $product?->material,
                'thickness'     => $this->num($data['thickness'] ?? null) ?? $nest->thickness,
                'sheet_x'       => $this->num($data['sheet_x'] ?? null) ?? $nest->sheet_x,
                'sheet_y'       => $this->num($data['sheet_y'] ?? null) ?? $nest->sheet_y,
                'sheet_qty'     => $this->num($done ?? $data['sheet_qty'] ?? null) ?? $nest->sheet_qty ?? 0,
                'usage_percent' => $this->num($data['sheet_usage'] ?? null) ?? $nest->usage_percent,
                'machine'       => $data['machine_name'] ?? $nest->machine,
                'status'        => self::N2P_STATUS[(int) ($data['status'] ?? 0)] ?? ($nest->status ?? Nest::STATUS_IN_PROGRESS),
                'cut_at'        => $data['finished_at'] ?? $nest->cut_at,
                'payload'       => $data,
            ])->save();

            if (array_key_exists('parts', $data) && is_array($data['parts'])) {
                $this->replaceN2PParts($nest, $data['parts']);
            }

            $this->recordPendingRemnants($nest);

            return $nest;
        });
    }

    /**
     * Imbrication envoyée par une FAO tierce (API `POST /api/nests`) : identifiée par
     * (`system`, `external_id`), rejouable. Les pièces sont remplacées à chaque appel ;
     * à la fin de la découpe (`done`), la tôle est sortie du stock et les chutes y sont
     * remises, **une seule fois**. Contrairement à Nest2Prod, l'appel est synchrone :
     * une sortie impossible refuse tout l'appel (rien n'est écrit), l'émetteur corrige
     * et renvoie.
     *
     * @param  array<string, mixed>  $data  champs validés (voir InboundNestController)
     * @return array{nest: Nest, created: bool, stock_recorded_now: bool, parts: array<int, array<string, mixed>>, warnings: list<string>}
     *
     * @throws \DomainException  article ou pièce de tôle introuvable, stock insuffisant, chute impossible
     */
    public function upsertFromImport(string $system, array $data, User $user): array
    {
        $externalId = mb_substr($system . ':' . $data['external_id'], 0, 255);
        $sheet = $data['sheet'] ?? [];
        $product = $this->sheetProduct($sheet);
        $status = $data['status'] ?? Nest::STATUS_DONE;

        return DB::transaction(function () use ($externalId, $data, $sheet, $product, $status, $user) {
            $nest = Nest::where('source', Nest::SOURCE_IMPORT)->where('external_id', $externalId)->lockForUpdate()->first();
            $created = $nest === null;
            $nest ??= new Nest(['source' => Nest::SOURCE_IMPORT, 'external_id' => $externalId, 'user_id' => $user->id]);
            $warnings = [];

            if (! $created && $nest->product_id && $nest->product_id !== $product->id && $nest->stockMoves()->exists()) {
                throw new \DomainException(__('nests.api.product_locked'));
            }

            $nest->fill([
                'name'          => $data['name'] ?? $nest->name ?? (string) $data['external_id'],
                'product_id'    => $product->id,
                'material'      => $data['material'] ?? $nest->material ?? $product->material,
                'thickness'     => $this->num($sheet['z_size'] ?? null) ?? $nest->thickness ?? $this->num($product->thickness),
                'sheet_x'       => $this->num($sheet['x_size'] ?? null) ?? $nest->sheet_x ?? $this->num($product->x_size),
                'sheet_y'       => $this->num($sheet['y_size'] ?? null) ?? $nest->sheet_y ?? $this->num($product->y_size),
                'sheet_qty'     => (float) $data['sheet_qty'],
                'usage_percent' => $this->num($data['usage_percent'] ?? null) ?? $nest->usage_percent,
                'machine'       => $data['machine'] ?? $nest->machine,
                'status'        => $status,
                'cut_at'        => $data['cut_at'] ?? $nest->cut_at ?? ($status === Nest::STATUS_DONE ? now() : null),
                'comment'       => $data['comment'] ?? $nest->comment,
                'payload'       => $data,
            ])->save();

            $parts = $this->replaceImportParts($nest, $data['parts'] ?? [], $product);
            foreach ($parts as $part) {
                if ($part['order_line_id'] === null) {
                    $warnings[] = 'part_unmatched:' . ($part['part_ref'] ?? $part['of_code'] ?? '?');
                }
            }

            $alreadyRecorded = $nest->stockMoves()->where('typ_move', self::EXIT_TYPE)->exists();
            $recordedNow = false;

            if ($status === Nest::STATUS_DONE && ($data['consume_stock'] ?? true) && ! $alreadyRecorded) {
                $this->consumeImportedSheet($nest, $product, $sheet, $data['remnants'] ?? [], $parts, $user);
                $recordedNow = true;
            } elseif ($alreadyRecorded && ($status !== Nest::STATUS_DONE || ! empty($data['remnants']))) {
                // Le stock déjà sorti n'est ni annulé ni refait : signalé, jamais rejoué.
                $warnings[] = 'stock_already_recorded';
            }

            return [
                'nest'               => $nest->fresh(),
                'created'            => $created,
                'stock_recorded_now' => $recordedNow,
                'parts'              => $parts,
                'warnings'           => array_values(array_unique($warnings)),
            ];
        });
    }

    /** Article tôle désigné par son code (ou son id) ; introuvable → refus. */
    private function sheetProduct(array $sheet): Products
    {
        $product = isset($sheet['product_id'])
            ? Products::find($sheet['product_id'])
            : Products::where('code', (string) ($sheet['product_code'] ?? ''))->first();

        if (! $product) {
            throw new \DomainException(__('nests.api.product_not_found'));
        }

        return $product;
    }

    /**
     * Sort la tôle découpée (pièce au format et au lot donnés, sinon au format de
     * l'imbrication, sinon au format de l'article) et remet les chutes en stock.
     */
    private function consumeImportedSheet(Nest $nest, Products $product, array $sheet, array $remnants, array $parts, User $user): void
    {
        $lines = StockLocationProducts::where('products_id', $product->id)
            ->when(isset($sheet['stock_line_code']), fn ($q) => $q->where('code', $sheet['stock_line_code']))
            ->orderBy('id')
            ->get();
        if ($lines->isEmpty()) {
            throw new \DomainException(__('nests.api.stock_line_not_found'));
        }

        $qty = (float) $nest->sheet_qty;
        $x = $nest->sheet_x;
        $y = $nest->sheet_y;
        $batch = isset($sheet['batch']) ? trim((string) $sheet['batch']) : null;

        foreach ($lines as $slp) {
            $piece = collect($this->remnants->pieces($slp))->first(fn ($p) => $p['qty'] + 0.0001 >= $qty
                && ($batch === null || $batch === '' || in_array($batch, [$p['batch_code'], $p['heat_number']], true))
                && self::sameSize($p['x_size'], $p['y_size'], $x, $y));

            if ($piece) {
                $taskIds = array_values(array_unique(array_filter(array_column($parts, 'task_id'))));
                $this->remnants->dispatchPiece($slp, $piece['key'], $qty, $this->remnants->normalize(array_map(
                    fn ($r) => ['x_size' => $r['width'] ?? null, 'y_size' => $r['height'] ?? null, 'qty' => $r['quantity'] ?? 1],
                    $remnants,
                )), [
                    'user_id'         => $user->id,
                    'typ_move'        => self::EXIT_TYPE,
                    'task_id'         => count($taskIds) === 1 ? $taskIds[0] : null,
                    'nest_id'         => $nest->id,
                    'component_price' => (float) $slp->unit_cost,
                ]);
                $nest->forceFill(['remnants_recorded_at' => now()])->save();

                return;
            }
        }

        throw new \DomainException(__('nests.api.piece_not_found', [
            'qty'    => rtrim(rtrim(number_format($qty, 3, '.', ''), '0'), '.'),
            'format' => $x && $y ? rtrim(rtrim((string) $x, '0'), '.') . ' × ' . rtrim(rtrim((string) $y, '0'), '.') : '—',
        ]));
    }

    /** Même format, rotation admise (0,5 mm près) ; format inconnu d'un côté = accepté. */
    private static function sameSize(?float $ax, ?float $ay, ?float $bx, ?float $by): bool
    {
        if (! ($ax > 0 && $ay > 0 && $bx > 0 && $by > 0)) {
            return true;
        }

        return (abs($ax - $bx) < 0.5 && abs($ay - $by) < 0.5) || (abs($ax - $by) < 0.5 && abs($ay - $bx) < 0.5);
    }

    /**
     * Pièces d'une imbrication importée, remplacées à chaque appel. Ligne de commande
     * par `order_line_id`, sinon `of_code` « OF<id> » ; tâche = celle de la ligne dont
     * le composant est la tôle.
     *
     * @return array<int, array<string, mixed>>
     */
    private function replaceImportParts(Nest $nest, array $parts, Products $product): array
    {
        $nest->parts()->delete();
        $out = [];

        foreach ($parts as $part) {
            $orderLine = isset($part['order_line_id'])
                ? OrderLines::find($part['order_line_id'])
                : $this->orderLineFromRef((string) ($part['of_code'] ?? ''), '');
            $task = $orderLine
                ? Task::where('order_lines_id', $orderLine->id)->where('component_id', $product->id)->orderBy('ordre')->orderBy('id')->first()
                : null;

            $row = $nest->parts()->create([
                'order_line_id' => $orderLine?->id,
                'task_id'       => $task?->id,
                'part_ref'      => $part['part_ref'] ?? null,
                'external_ref'  => $part['external_ref'] ?? null,
                'qty'           => (float) $part['qty'],
                'good_qty'      => $this->num($part['good_qty'] ?? null),
                'bad_qty'       => $this->num($part['bad_qty'] ?? null),
                'area_mm2'      => $this->num($part['area_mm2'] ?? null),
                'weight_kg'     => $this->num($part['weight_kg'] ?? null),
            ]);

            $out[] = [
                'id'            => $row->id,
                'part_ref'      => $row->part_ref,
                'of_code'       => $part['of_code'] ?? null,
                'order_line_id' => $row->order_line_id,
                'task_id'       => $row->task_id,
            ];
        }

        return $out;
    }

    /**
     * Sortie `stock.consumed` de Nest2Prod rattachée à son imbrication (créée en
     * attente si `nest.updated` n'est pas encore arrivé) et valorisée au coût moyen.
     */
    public function attachN2PConsumption(StockMove $move, array $consumedBy): ?Nest
    {
        $externalId = (string) ($consumedBy['nest_id'] ?? '');
        if ($externalId === '') {
            return null;
        }

        $slp = $move->StockLocationProducts;
        $nest = Nest::firstOrCreate(
            ['source' => Nest::SOURCE_N2P, 'external_id' => $externalId],
            [
                'name'       => (string) ($consumedBy['nest_name'] ?? ('N2P #' . $externalId)),
                'product_id' => $slp?->products_id,
                'status'     => Nest::STATUS_IN_PROGRESS,
            ],
        );
        if (! $nest->product_id && $slp) {
            $nest->forceFill(['product_id' => $slp->products_id])->save();
        }

        $move->forceFill(['nest_id' => $nest->id]);
        if (! ((float) $move->component_price > 0) && (float) $slp?->unit_cost > 0) {
            $move->component_price = (float) $slp->unit_cost;
        }
        $move->save();

        $this->recordPendingRemnants($nest);

        return $nest;
    }

    /**
     * Chutes d'une imbrication Nest2Prod terminée, remises en stock une seule fois,
     * sur la ligne et le lot de sa dernière sortie. Sans sortie encore reçue : on
     * attend le prochain message. Une chute impossible (plus grande que la tôle…)
     * est ignorée et journalisée : le message ne doit pas être refusé en boucle.
     */
    public function recordPendingRemnants(Nest $nest): void
    {
        $rows = $nest->payload['remnants'] ?? [];
        if ($nest->source !== Nest::SOURCE_N2P || $nest->status !== Nest::STATUS_DONE
            || $nest->remnants_recorded_at !== null || ! is_array($rows) || $rows === []) {
            return;
        }

        $exit = $nest->stockMoves()->where('typ_move', self::EXIT_TYPE)->orderByDesc('id')->first();
        if (! $exit) {
            return;
        }

        $input = [];
        foreach ($rows as $row) {
            if (! is_array($row) || ($row['is_available'] ?? true) === false) {
                continue;
            }
            $input[] = ['x_size' => $row['width'] ?? null, 'y_size' => $row['height'] ?? null, 'qty' => $row['quantity'] ?? 1];
        }

        $consumed = (float) $nest->stockMoves()->where('typ_move', self::EXIT_TYPE)->sum('qty');

        try {
            $this->remnants->putBack(
                $exit,
                $this->remnants->normalize($input),
                $consumed > 0 ? $consumed : (float) $exit->qty,
                $nest->sheet_x,
                $nest->sheet_y,
                $nest->thickness,
            );
        } catch (\DomainException $e) {
            Log::warning('N2P nest remnants ignored', ['nest' => $nest->id, 'error' => $e->getMessage()]);
        }

        $nest->forceFill(['remnants_recorded_at' => now()])->save();
    }

    /**
     * Coût matière de l'imbrication et sa répartition par ligne de commande.
     *
     * Coût = sorties (valeur de la sortie, sinon coût moyen de la ligne de stock)
     * − chutes rendues. Répartition au prorata de la surface des pièces, sinon de
     * leur poids, sinon de leur nombre : la base est la même pour toute l'imbrication.
     *
     * @return array{cost: float, basis: string, lines: array<int, array<string, mixed>>}
     */
    public function allocation(Nest $nest): array
    {
        $moves = $nest->stockMoves()->with('StockLocationProducts:id,unit_cost')->get();
        $cost = 0.0;
        foreach ($moves as $move) {
            // Sortie non valorisée (0, valeur par défaut) : coût moyen actuel de la ligne.
            $price = (float) $move->component_price > 0 ? (float) $move->component_price : (float) $move->StockLocationProducts?->unit_cost;
            if (in_array((int) $move->typ_move, self::OUTGOING, true)) {
                $cost += (float) $move->qty * $price;
            } elseif ($move->remnant_source_move_id !== null) {
                $cost -= (float) $move->qty * (float) $move->component_price;
            }
        }
        $cost = max(0.0, round($cost, 2));

        $parts = $nest->parts()->with('orderLine.order')->get();
        $basis = match (true) {
            $parts->isNotEmpty() && $parts->every(fn ($p) => $p->area_mm2 > 0)  => 'area',
            $parts->isNotEmpty() && $parts->every(fn ($p) => $p->weight_kg > 0) => 'weight',
            default                                                              => 'qty',
        };
        $weightOf = fn (NestPart $p) => $p->qty * match ($basis) {
            'area'   => $p->area_mm2,
            'weight' => $p->weight_kg,
            default  => 1,
        };
        $total = $parts->sum($weightOf);

        $lines = $parts->groupBy(fn ($p) => $p->order_line_id ?? 0)->map(function ($group) use ($weightOf, $total, $cost) {
            $share = $total > 0 ? $group->sum($weightOf) / $total : 0;
            $line = $group->first()->orderLine;

            return [
                'order_line_id' => $line?->id,
                'order_code'    => $line?->order?->code,
                'order_id'      => $line?->order?->id,
                'label'         => $line?->label ?? $group->first()->part_ref,
                'qty'           => $group->sum('qty'),
                'share'         => $share,
                'cost'          => round($cost * $share, 2),
            ];
        })->values()->all();

        return ['cost' => $cost, 'basis' => $basis, 'lines' => $lines];
    }

    /** @param  array<int, mixed>  $parts */
    private function replaceN2PParts(Nest $nest, array $parts): void
    {
        $nest->parts()->delete();

        foreach ($parts as $part) {
            if (! is_array($part)) {
                continue;
            }
            $orderLine = $this->orderLineFromRef((string) ($part['of_code'] ?? ''), (string) ($part['line_ref'] ?? ''));
            $task = $orderLine && $nest->product_id
                ? Task::where('order_lines_id', $orderLine->id)->where('component_id', $nest->product_id)->orderBy('ordre')->orderBy('id')->first()
                : null;

            $nest->parts()->create([
                'order_line_id' => $orderLine?->id,
                'task_id'       => $task?->id,
                'part_ref'      => $part['part_name'] ?? null,
                'external_ref'  => isset($part['external_part_number']) ? (string) $part['external_part_number'] : null,
                'qty'           => $this->num($part['planned_qty'] ?? $part['good_qty'] ?? null) ?? 0,
                'good_qty'      => $this->num($part['good_qty'] ?? null),
                'bad_qty'       => $this->num($part['bad_qty'] ?? null),
                'area_mm2'      => $this->num($part['area_mm2'] ?? null),
                'weight_kg'     => $this->num($part['weight_kg'] ?? null),
            ]);
        }
    }

    /** Même règle que Task::resolveByExternalRef : « OF<id de ligne> », sinon line_ref numérique. */
    private function orderLineFromRef(string $ofCode, string $lineRef): ?OrderLines
    {
        $id = str_starts_with($ofCode, 'OF') ? (int) substr($ofCode, 2) : (ctype_digit($lineRef) ? (int) $lineRef : 0);

        return $id > 0 ? OrderLines::find($id) : null;
    }

    /** Article tôle de l'imbrication : celui du premier lot consommé (« MV-<id> »). */
    private function productFromLots($lots): ?Products
    {
        if (! is_array($lots)) {
            return null;
        }
        $prefix = SheetLotPayloadBuilder::EXTERNAL_REF_PREFIX;
        foreach ($lots as $lot) {
            $ref = (string) ($lot['external_ref'] ?? '');
            if (str_starts_with($ref, $prefix)) {
                $move = StockMove::with('StockLocationProducts.Product')->find((int) substr($ref, strlen($prefix)));
                if ($move?->StockLocationProducts?->Product) {
                    return $move->StockLocationProducts->Product;
                }
            }
        }

        return null;
    }

    /** « 78,4 % » → 78.4 ; vide → null. */
    private function num($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        $clean = str_replace(',', '.', preg_replace('/[^0-9,.\-]/', '', (string) $value));

        return is_numeric($clean) ? (float) $clean : null;
    }
}
