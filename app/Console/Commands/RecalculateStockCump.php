<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Products\StockLocationProducts;
use App\Services\StockCalculationService;

class RecalculateStockCump extends Command
{
    protected $signature   = 'stock:recalculate-cump {--dry-run : Affiche les nouvelles valeurs sans les enregistrer}';
    protected $description = 'Recalcule le CUMP historique complet pour tous les emplacements produit';

    public function handle(StockCalculationService $calculator): int
    {
        $dryRun = $this->option('dry-run');
        $rows   = StockLocationProducts::all();
        $bar    = $this->output->createProgressBar($rows->count());
        $bar->start();

        $updated = 0;

        foreach ($rows as $slp) {
            $cump = $calculator->calculateWeightedAverageCost($slp->id);

            if ($cump > 0 && $cump != (float) $slp->unit_cost) {
                if (!$dryRun) {
                    $slp->unit_cost = round($cump, 4);
                    $slp->save();
                }
                $updated++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $label = $dryRun ? 'à mettre à jour' : 'mis à jour';
        $this->info("{$updated} enregistrement(s) {$label}.");

        return self::SUCCESS;
    }
}
