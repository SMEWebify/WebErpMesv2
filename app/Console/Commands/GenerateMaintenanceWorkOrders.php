<?php

namespace App\Console\Commands;

use App\Services\Maintenance\PreventiveMaintenanceScheduler;
use Illuminate\Console\Command;

class GenerateMaintenanceWorkOrders extends Command
{
    protected $signature = 'gmao:generate-work-orders {--dry-run : Lister les ordres dus sans les créer}';

    protected $description = 'Crée les ordres de travail préventifs des plans de maintenance arrivés à échéance (génération automatique activée).';

    public function handle(PreventiveMaintenanceScheduler $scheduler): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $results = $scheduler->generateDue(null, $dryRun);

        foreach ($results as $row) {
            $this->line(sprintf(
                '%s plan #%d « %s » — échéance %s%s',
                $dryRun ? '[simulation]' : '✓',
                $row['plan']->id,
                $row['plan']->title,
                $row['due']->format('d/m/Y'),
                $row['work_order'] ? ' → ordre #' . $row['work_order']->id : '',
            ));
        }

        $this->info($results->count() . ($dryRun ? ' ordre(s) à créer.' : ' ordre(s) créé(s).'));

        return self::SUCCESS;
    }
}
