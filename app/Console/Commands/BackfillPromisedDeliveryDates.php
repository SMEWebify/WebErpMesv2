<?php

namespace App\Console\Commands;

use App\Models\Workflow\OrderLineDelayHistory;
use App\Models\Workflow\OrderLines;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Reprise de order_lines.promised_delivery_date pour les lignes antérieures
 * à la colonne.
 *
 * Source, par ordre de préférence :
 *   1. l'ancienne valeur du premier report historisé de delivery_date (la date
 *      d'avant le premier report connu) ;
 *   2. à défaut, la delivery_date actuelle.
 *
 * Une ligne déjà renseignée n'est jamais touchée : la date promise ne se
 * modifie pas. Une ligne sans aucune date reste nulle. L'écriture passe par
 * une requête directe, sans OrderLinesObserver : il n'y a aucun report à
 * historiser ici.
 *
 * Limite : avant l'historique, les reports écrasaient delivery_date. Pour une
 * ligne déjà reportée avant la mise en place, la valeur reprise est la date en
 * vigueur, pas celle promise à l'origine — la reprise ne peut pas faire mieux.
 */
class BackfillPromisedDeliveryDates extends Command
{
    protected $signature = 'wem:orders:backfill-promised-dates {--dry-run : Show what would change without saving}';

    protected $description = 'Fill order_lines.promised_delivery_date for existing lines (first recorded date, else current delivery date)';

    public function handle(): int
    {
        $dryRun  = (bool) $this->option('dry-run');
        $scanned = 0;
        $fromHistory = 0;
        $fromCurrent = 0;
        $skipped = 0;

        OrderLines::query()
            ->whereNull('promised_delivery_date')
            ->select(['id', 'orders_id', 'delivery_date'])
            ->chunkById(500, function ($lines) use ($dryRun, &$scanned, &$fromHistory, &$fromCurrent, &$skipped) {
                // Premier report de delivery_date de chaque ligne du lot, en une requête.
                $firstOld = OrderLineDelayHistory::query()
                    ->whereIn('order_lines_id', $lines->pluck('id'))
                    ->where('field', OrderLineDelayHistory::FIELD_DELIVERY_DATE)
                    ->orderBy('id')
                    ->get(['order_lines_id', 'old_value'])
                    ->unique('order_lines_id')
                    ->pluck('old_value', 'order_lines_id');

                foreach ($lines as $line) {
                    $scanned++;

                    $historic = $firstOld->get($line->id);
                    $date     = self::toDate($historic) ?? self::toDate($line->delivery_date);

                    if ($date === null) {
                        $skipped++;
                        continue;
                    }

                    $source = self::toDate($historic) !== null ? 'history' : 'current';
                    $source === 'history' ? $fromHistory++ : $fromCurrent++;

                    if ($this->output->isVerbose()) {
                        $this->line(sprintf('#%d (order %d) : %s [%s]', $line->id, $line->orders_id, $date, $source));
                    }

                    if (!$dryRun) {
                        OrderLines::query()
                            ->whereKey($line->id)
                            ->whereNull('promised_delivery_date')
                            ->toBase() // sans toucher updated_at
                            ->update(['promised_delivery_date' => $date]);
                    }
                }
            });

        $this->table(['', 'Lines'], [
            ['Scanned (no promised date)', $scanned],
            ['From first recorded change', $fromHistory],
            ['From current delivery date', $fromCurrent],
            ['Left empty (no date at all)', $skipped],
        ]);

        $this->info(sprintf(
            '%d line(s) %s.%s',
            $fromHistory + $fromCurrent,
            $dryRun ? 'would be updated (dry run, nothing saved)' : 'updated',
            $dryRun ? ' Use -v to list them.' : ''
        ));

        return self::SUCCESS;
    }

    private static function toDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
