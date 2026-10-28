<?php

namespace App\Services\Quotes;

use App\Models\Workflow\AffairCategory;
use App\Models\Workflow\LossReason;
use App\Models\Workflow\Quotes;
use App\Services\Workflow\LossReasonRecorder;
use Illuminate\Support\Facades\DB;

/**
 * Indicateur « pertes commerciales » : devis perdus par motif et par catégorie
 * d'affaire, en nombre et en montant HT (même calcul que la colonne Total de
 * l'index des devis). Les devis perdus sans motif (antérieurs au référentiel)
 * sont comptés à part, jamais écartés.
 *
 * Trois requêtes quelle que soit la volumétrie : l'agrégat, puis les libellés.
 */
final class QuoteLossStats
{
    public const PERIODS = ['12m', 'year', 'all'];

    /** Clé des lignes sans motif / sans catégorie. */
    public const NONE = 'none';

    public function compute(string $period = '12m'): array
    {
        $totals = DB::table('quote_lines')
            ->whereNull('deleted_at')
            ->groupBy('quotes_id')
            ->selectRaw('quotes_id, SUM(selling_price * qty * (1 - COALESCE(discount, 0) / 100)) as amount');

        // Le scope global de Quotes écarte les trames ; toBase() le conserve.
        $rows = Quotes::query()
            ->where('quotes.statu', LossReasonRecorder::QUOTE_LOST)
            ->when($period === '12m', fn ($q) => $q->where('quotes.created_at', '>=', now()->subMonths(12)->startOfDay()))
            ->when($period === 'year', fn ($q) => $q->where('quotes.created_at', '>=', now()->startOfYear()))
            ->leftJoinSub($totals, 'quote_totals', 'quote_totals.quotes_id', '=', 'quotes.id')
            ->groupBy('quotes.loss_reason_id', 'quotes.affair_category_id')
            ->selectRaw('quotes.loss_reason_id, quotes.affair_category_id, COUNT(*) as quotes_count, COALESCE(SUM(quote_totals.amount), 0) as amount')
            ->toBase()
            ->get();

        $reasonLabels   = LossReason::whereIn('id', $rows->pluck('loss_reason_id')->filter()->unique())->pluck('label', 'id');
        $categoryLabels = AffairCategory::whereIn('id', $rows->pluck('affair_category_id')->filter()->unique())->pluck('label', 'id');

        $totalCount  = (int) $rows->sum('quotes_count');
        $totalAmount = round((float) $rows->sum('amount'), 2);

        $reasons = $rows->groupBy(fn ($r) => $r->loss_reason_id ?? self::NONE)
            ->map(fn ($group, $key) => [
                'key'    => (string) $key,
                'label'  => $key === self::NONE ? __('losses.none') : ($reasonLabels[$key] ?? '#'.$key),
                'count'  => (int) $group->sum('quotes_count'),
                'amount' => round((float) $group->sum('amount'), 2),
            ])
            ->values()
            ->sort(fn ($a, $b) => [$a['key'] === self::NONE, -$a['count'], -$a['amount']] <=> [$b['key'] === self::NONE, -$b['count'], -$b['amount']])
            ->values()
            ->map(fn ($r) => $r + ['share' => $totalCount > 0 ? round($r['count'] * 100 / $totalCount, 1) : 0.0])
            ->all();

        $categories = $rows->groupBy(fn ($r) => $r->affair_category_id ?? self::NONE)
            ->map(fn ($group, $key) => [
                'key'       => (string) $key,
                'label'     => $key === self::NONE ? __('losses.stats.no_category') : ($categoryLabels[$key] ?? '#'.$key),
                'count'     => (int) $group->sum('quotes_count'),
                'amount'    => round((float) $group->sum('amount'), 2),
                'by_reason' => $group->groupBy(fn ($r) => (string) ($r->loss_reason_id ?? self::NONE))
                    ->map(fn ($g) => (int) $g->sum('quotes_count'))
                    ->all(),
            ])
            ->values()
            ->sort(fn ($a, $b) => [$a['key'] === self::NONE, -$a['count']] <=> [$b['key'] === self::NONE, -$b['count']])
            ->values()
            ->all();

        return [
            'period'     => $period,
            'total'      => ['count' => $totalCount, 'amount' => $totalAmount],
            'reasons'    => $reasons,
            'categories' => $categories,
        ];
    }
}
