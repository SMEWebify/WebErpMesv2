<?php

namespace App\Console\Commands;

use App\Models\Purchases\PurchaseLines;
use Illuminate\Console\Command;

/**
 * Reprise de purchase_lines.unit_price_after_discount / total_selling_price.
 *
 * L'écran React des lignes d'achat ne les renseignait pas : une ligne saisie
 * y restait à 0, une ligne modifiée y gardait l'ancien montant. L'écran
 * « En attente de facturation », le dossier d'affaire et les KPI achats lisent
 * pourtant ces colonnes.
 *
 * Seules les lignes dont la valeur stockée diffère du calcul
 * (selling_price × qty × (1 − discount)) sont réécrites. Les autres chemins de
 * création (tâches, demande de prix, réappro, réception manuelle) stockent déjà
 * ce calcul et ne sont donc pas touchés. Les écritures comptables déjà passées
 * ne sont pas recalculées.
 */
class BackfillPurchaseLineTotals extends Command
{
    protected $signature = 'wem:purchases:backfill-line-totals {--dry-run : Show what would change without saving}';

    protected $description = 'Recompute purchase_lines.unit_price_after_discount / total_selling_price where they differ from price × qty × (1 − discount)';

    public function handle(): int
    {
        $dryRun  = (bool) $this->option('dry-run');
        $scanned = 0;
        $fixed   = 0;

        PurchaseLines::query()
            ->select(['id', 'purchases_id', 'qty', 'selling_price', 'discount', 'unit_price_after_discount', 'total_selling_price'])
            ->chunkById(500, function ($lines) use ($dryRun, &$scanned, &$fixed) {
                foreach ($lines as $line) {
                    $scanned++;

                    $expected = PurchaseLines::netPrices(
                        $line->getRawOriginal('selling_price'),
                        $line->qty,
                        $line->discount
                    );

                    $unitOk  = abs((float) $line->unit_price_after_discount - $expected['unit_price_after_discount']) < 0.0005;
                    $totalOk = abs((float) $line->total_selling_price - $expected['total_selling_price']) < 0.0005;

                    if ($unitOk && $totalOk) {
                        continue;
                    }

                    $fixed++;

                    if ($this->output->isVerbose()) {
                        $this->line(sprintf(
                            '#%d (purchase %d) : %s / %s -> %s / %s',
                            $line->id,
                            $line->purchases_id,
                            (float) $line->unit_price_after_discount,
                            (float) $line->total_selling_price,
                            $expected['unit_price_after_discount'],
                            $expected['total_selling_price']
                        ));
                    }

                    if (!$dryRun) {
                        PurchaseLines::query()
                            ->whereKey($line->id)
                            ->toBase() // sans toucher updated_at
                            ->update($expected);
                    }
                }
            });

        $this->table(['', 'Lines'], [
            ['Scanned', $scanned],
            ['Out of date', $fixed],
        ]);

        $this->info(sprintf(
            '%d line(s) %s.%s',
            $fixed,
            $dryRun ? 'would be updated (dry run, nothing saved)' : 'updated',
            $dryRun ? ' Use -v to list them.' : ''
        ));

        return self::SUCCESS;
    }
}
