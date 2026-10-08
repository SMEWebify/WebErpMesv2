<?php

namespace App\Console\Commands;

use App\Services\Integrations\Signature\QuoteSignatureService;
use Illuminate\Console\Command;

/**
 * Relit les enveloppes de signature en attente.
 *
 * Filet de sécurité de la notification DocuSign : une instance non publiée en
 * HTTPS ne la reçoit jamais, et un client qui ferme l'onglet avant le retour
 * ne déclenche pas non plus la relecture.
 */
class ESignatureSyncCommand extends Command
{
    protected $signature = 'wem:esign:sync';

    protected $description = 'Synchronise les signatures électroniques de devis en attente (DocuSign)';

    public function handle(QuoteSignatureService $service): int
    {
        $result = $service->syncPending();

        $this->info("{$result['checked']} enveloppe(s) relue(s), {$result['completed']} signée(s), {$result['failed']} en échec.");

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
