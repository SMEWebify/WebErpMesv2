<?php

namespace App\Console\Commands;

use App\Models\Integrations\SharePointSetting;
use App\Services\Integrations\SharePoint\SharePointMirror;
use Illuminate\Console\Command;

/**
 * Met en file la recopie SharePoint des fichiers existants de la GED (dernières versions
 * pas encore recopiées), et relance les recopies en échec. Sans liaison active, ne fait rien.
 */
class SharePointSyncCommand extends Command
{
    protected $signature = 'wem:sharepoint:sync
                            {--failed : Relancer seulement les recopies en échec}
                            {--dry-run : Compter sans rien mettre en file}';

    protected $description = 'Recopie dans SharePoint les fichiers de la GED pas encore recopiés';

    public function handle(SharePointMirror $mirror): int
    {
        if (! SharePointSetting::usable()) {
            $this->info('SharePoint : liaison inactive ou jamais testée avec succès.');

            return self::SUCCESS;
        }

        if ($this->option('failed')) {
            $count = $this->option('dry-run')
                ? $mirror->stats()['failed']
                : $mirror->retryFailed();
            $this->line("SharePoint : {$count} recopie(s) en échec " . ($this->option('dry-run') ? 'à relancer.' : 'relancée(s).'));

            return self::SUCCESS;
        }

        $count = $mirror->backfill((bool) $this->option('dry-run'));
        $this->line("SharePoint : {$count} fichier(s) " . ($this->option('dry-run') ? 'à recopier.' : 'mis en file.'));

        return self::SUCCESS;
    }
}
