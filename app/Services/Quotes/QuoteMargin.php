<?php

namespace App\Services\Quotes;

use App\Models\Planning\SubAssembly;
use App\Models\Planning\Task;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\Quotes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Marge d'un devis, information interne : jamais imprimée.
 *
 * Coût de revient unitaire d'une ligne article = opérations (types 1 et 7,
 * sous-traitance comprise, pondérées par leur part de la ligne) + nomenclature
 * (types 2-6 et 8, coût × quantité) + sous-ensembles (quantité × coût du
 * sous-ensemble, calculé de la même façon sur toute la profondeur).
 * Le coût unitaire d'une opération a déjà le réglage réparti sur la quantité
 * de la ligne (TaskManagePage) : il n'est pas ajouté une seconde fois.
 *
 * Marge en % = sur le coût, comme `methods_services.margin` et Task::Margin().
 * Lignes de présentation ignorées. Requêtes en nombre constant (une par
 * niveau de sous-ensemble en plus).
 */
class QuoteMargin
{
    private const OPERATION_TYPES = [1, 7];
    private const BOM_TYPES       = [2, 3, 4, 5, 6, 8];

    /**
     * @param  Collection<int, QuoteLines>  $lines
     * @return array{lines: array<int, array>, totals: array}
     */
    public function forLines(Collection $lines): array
    {
        $articles  = $lines->filter->isArticle()->values();
        $unitCosts = $this->unitCosts($articles);
        $threshold = (float) config('quotes.margin_alert_threshold', 0);

        $rows   = [];
        $cost   = 0.0;
        $sale   = 0.0;
        $noCost = 0;

        foreach ($articles as $line) {
            $row = $this->row($line, $unitCosts[$line->id] ?? 0.0, $threshold);
            $rows[$line->id] = $row;

            $cost += $row['cost_total'];
            $sale += $row['sale_total'];
            if (! $row['has_cost']) {
                $noCost++;
            }
        }

        return [
            'lines'  => $rows,
            'totals' => [
                'cost_total'       => round($cost, 2),
                'sale_total'       => round($sale, 2),
                'margin'           => round($sale - $cost, 2),
                'margin_rate'      => self::rate($sale, $cost),
                'markup_rate'      => $sale > 0 ? round(($sale - $cost) / $sale * 100, 2) : null,
                'lines_without_cost' => $noCost,
                'below_threshold'  => $cost > 0 && self::rate($sale, $cost) < $threshold,
                'threshold'        => $threshold,
            ],
        ];
    }

    public function forQuote(Quotes $quote): array
    {
        return $this->forLines($quote->QuoteLines);
    }

    /**
     * Ligne d'un devis convertie en commande : prix figé, l'action globale est refusée.
     */
    public function isConverted(Quotes $quote): bool
    {
        return $quote->Orders()->exists()
            || OrderLines::whereIn('quote_lines_id', QuoteLines::where('quotes_id', $quote->id)->select('id'))->exists();
    }

    /**
     * Recalcule le prix de vente des lignes article à partir de leur coût :
     * prix = coût × (1 + taux) / (1 − remise), pour que la marge après remise
     * soit celle saisie. Lignes sans coût ou remisées à 100 % laissées telles quelles.
     *
     * @return array{updated: int, skipped: int}
     *
     * @throws QuoteMarginRefused
     */
    public function apply(Quotes $quote, float $rate): array
    {
        if ($this->isConverted($quote)) {
            throw new QuoteMarginRefused(__('quote_margin.refused_converted'));
        }
        if ((int) $quote->statu !== 1) {
            throw new QuoteMarginRefused(__('quote_margin.refused_status'));
        }

        return DB::transaction(function () use ($quote, $rate) {
            $lines     = QuoteLines::where('quotes_id', $quote->id)->articles()->lockForUpdate()->get();
            $unitCosts = $this->unitCosts($lines);
            $updated   = 0;
            $skipped   = 0;

            foreach ($lines as $line) {
                $unitCost = $unitCosts[$line->id] ?? 0.0;
                $discount = (float) $line->discount;

                if ($unitCost <= 0 || $discount >= 100) {
                    $skipped++;
                    continue;
                }

                // Le prix saisi remplace le prix calculé depuis la gamme.
                $line->use_calculated_price = false;
                $line->selling_price = round($unitCost * (1 + $rate / 100) / (1 - $discount / 100), 2);
                $line->save();
                $updated++;
            }

            return ['updated' => $updated, 'skipped' => $skipped];
        });
    }

    public static function rate(float $sale, float $cost): ?float
    {
        return $cost > 0 ? round(($sale - $cost) / $cost * 100, 2) : null;
    }

    private function row(QuoteLines $line, float $unitCost, float $threshold): array
    {
        $qty       = (float) $line->qty;
        $costTotal = round($unitCost * $qty, 2);
        // Même calcul que QuoteCalculatorService::getSubTotal().
        $saleTotal = round($qty * (float) $line->selling_price * (1 - (float) $line->discount / 100), 2);
        $rate      = self::rate($saleTotal, $costTotal);

        return [
            'unit_cost'       => round($unitCost, 4),
            'cost_total'      => $costTotal,
            'sale_total'      => $saleTotal,
            'margin'          => round($saleTotal - $costTotal, 2),
            'margin_rate'     => $rate,
            'has_cost'        => $unitCost > 0,
            'below_threshold' => $rate !== null && $rate < $threshold,
        ];
    }

    /**
     * Coût de revient unitaire de chaque ligne.
     *
     * @param  Collection<int, QuoteLines>  $lines
     * @return array<int, float> id de ligne → coût unitaire
     */
    public function unitCosts(Collection $lines): array
    {
        $lines = $lines->keyBy('id');
        if ($lines->isEmpty()) {
            return [];
        }

        $columns = ['id', 'quote_lines_id', 'sub_assembly_id', 'type', 'unit_cost', 'qty', 'planned_qty'];

        // Une opération rangée sous un sous-ensemble est comptée avec lui.
        $lineTasks = Task::query()->select($columns)
            ->whereIn('quote_lines_id', $lines->keys())
            ->whereNull('sub_assembly_id')
            ->get()
            ->groupBy('quote_lines_id');

        // Arbre des sous-ensembles, niveau par niveau.
        $roots = SubAssembly::query()->select(['id', 'quote_lines_id', 'sub_assembly_id', 'qty'])
            ->whereIn('quote_lines_id', $lines->keys())
            ->whereNull('sub_assembly_id')
            ->get();

        $children = collect();
        $all      = $roots->keyBy('id');
        $level    = $roots->pluck('id');
        while ($level->isNotEmpty()) {
            $next = SubAssembly::query()->select(['id', 'quote_lines_id', 'sub_assembly_id', 'qty'])
                ->whereIn('sub_assembly_id', $level)
                ->whereNotIn('id', $all->keys())
                ->get();
            foreach ($next as $sub) {
                $all[$sub->id] = $sub;
            }
            $children = $children->merge($next);
            $level    = $next->pluck('id');
        }
        $childrenOf = $children->groupBy('sub_assembly_id');

        $subTasks = $all->isEmpty() ? collect() : Task::query()->select($columns)
            ->whereIn('sub_assembly_id', $all->keys())
            ->get()
            ->groupBy('sub_assembly_id');

        $memo     = [];
        $subCost  = function (SubAssembly $sub, array $path = []) use (&$subCost, &$memo, $subTasks, $childrenOf): float {
            if (isset($memo[$sub->id])) {
                return $memo[$sub->id];
            }
            if (isset($path[$sub->id])) {
                return 0.0; // boucle dans la nomenclature : ne pas compter deux fois
            }
            $path[$sub->id] = true;

            $cost = $this->tasksCost($subTasks->get($sub->id, collect()), (float) $sub->qty);
            foreach ($childrenOf->get($sub->id, collect()) as $child) {
                $cost += (float) $child->qty * $subCost($child, $path);
            }

            return $memo[$sub->id] = $cost;
        };

        $rootsByLine = $roots->groupBy('quote_lines_id');
        $costs       = [];
        foreach ($lines as $id => $line) {
            $cost = $this->tasksCost($lineTasks->get($id, collect()), (float) $line->qty);
            foreach ($rootsByLine->get($id, collect()) as $sub) {
                $cost += (float) $sub->qty * $subCost($sub);
            }
            $costs[$id] = $cost;
        }

        return $costs;
    }

    /**
     * Coût unitaire d'un ensemble d'opérations et de composants pour une
     * quantité de porteur donnée (ligne ou sous-ensemble).
     */
    private function tasksCost(Collection $tasks, float $carrierQty): float
    {
        return $tasks->reduce(function (float $sum, Task $task) use ($carrierQty) {
            $type = (int) $task->type;
            if (in_array($type, self::OPERATION_TYPES, true)) {
                return $sum + (float) $task->unit_cost * $task->lineShare($carrierQty);
            }
            if (in_array($type, self::BOM_TYPES, true)) {
                return $sum + (float) $task->unit_cost * (float) $task->qty;
            }

            return $sum;
        }, 0.0);
    }
}
