<?php

namespace App\Console\Commands;

use App\Models\Integrations\PdpIncomingInvoice;
use App\Services\Integrations\Pdp\Contracts\PdpCursorSyncGateway;
use App\Services\Integrations\Pdp\Contracts\PdpInboundGateway;
use App\Services\Integrations\Pdp\PdpIncomingInvoiceService;
use App\Services\Integrations\Pdp\PdpInvoiceService;
use App\Services\Integrations\Pdp\PdpManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Synchronisation périodique avec la Plateforme de Dématérialisation Partenaire.
 *
 * Nécessaire pour les plateformes sans webhooks (SUPER PDP) : c'est ce passage
 * régulier qui fait remonter dans WEM le cycle de vie des factures émises
 * (reçue, acceptée, refusée, payée) et les factures fournisseurs entrantes.
 *
 * Sans lui, l'obligation de **réception** au 1er septembre 2026 n'est pas
 * couverte : les factures arriveraient chez la plateforme sans jamais entrer
 * dans l'ERP. À planifier (cf. routes/console.php).
 */
class PdpSyncCommand extends Command
{
    protected $signature = 'wem:pdp:sync
                            {--tenant=0 : Identifiant du tenant (0 = installation mono-société)}
                            {--events : Ne synchroniser que le cycle de vie des factures émises}
                            {--inbound : Ne synchroniser que les factures fournisseurs entrantes}';

    protected $description = "Synchronise le cycle de vie des factures émises et les factures fournisseurs reçues auprès de la PDP";

    public function handle(
        PdpManager $manager,
        PdpInvoiceService $invoiceService,
        PdpIncomingInvoiceService $incomingService,
    ): int {
        $gateway = $manager->driver();

        if (! $gateway->isEnabled()) {
            $this->warn("Le driver PDP [{$gateway->key()}] n'est pas configuré : rien à synchroniser.");
            return self::SUCCESS;
        }

        if (! $gateway instanceof PdpCursorSyncGateway) {
            $this->info("Le driver PDP [{$gateway->key()}] ne se synchronise pas par curseur (webhooks) : rien à faire.");
            return self::SUCCESS;
        }

        $tenantId = (int) $this->option('tenant');

        // Sans option explicite, les deux flux sont synchronisés.
        $only        = $this->option('events') || $this->option('inbound');
        $doEvents    = ! $only || $this->option('events');
        $doInbound   = (! $only || $this->option('inbound')) && $gateway instanceof PdpInboundGateway;

        if ($doEvents) {
            $this->syncEvents($gateway, $invoiceService, $tenantId);
        }

        if ($doInbound) {
            $this->syncInbound($gateway, $incomingService, $tenantId);
        }

        return self::SUCCESS;
    }

    /**
     * Cycle de vie de NOS factures émises.
     *
     * Le curseur n'avance qu'après application effective de chaque événement :
     * si l'un d'eux échoue, la prochaine exécution reprend exactement là.
     */
    private function syncEvents(
        PdpCursorSyncGateway $gateway,
        PdpInvoiceService $invoiceService,
        int $tenantId,
    ): void {
        $events = $gateway->fetchEvents($tenantId);

        if ($events === []) {
            $this->line('Cycle de vie : aucun nouvel événement.');
            return;
        }

        $applied = 0;
        $foreign = 0;

        foreach ($events as $event) {
            $eventId = (int) ($event->raw['id'] ?? 0);

            try {
                $matched = $invoiceService->handleWebhook($gateway->key(), $event);
            } catch (\Throwable $e) {
                // On s'arrête sur le premier échec : avancer le curseur ferait
                // perdre définitivement cet événement et tous ceux d'après.
                Log::error('PdpSync: event application failed, cursor held', [
                    'provider' => $gateway->key(),
                    'event_id' => $eventId,
                    'error'    => $e->getMessage(),
                ]);
                $this->error("Événement #{$eventId} en échec : {$e->getMessage()}");
                break;
            }

            if ($eventId > 0) {
                $gateway->commitEvents($tenantId, $eventId);
            }

            $matched ? $applied++ : $foreign++;
        }

        // Distinguer les deux : un événement lu n'est pas un événement appliqué.
        // Le compte de la plateforme peut porter des factures déposées par un
        // autre outil, dont les événements ne concernent aucune facture WEM.
        $message = "Cycle de vie : {$applied} événement(s) appliqué(s) sur " . count($events) . ' lu(s)';
        $message .= $foreign ? ", {$foreign} sans facture WEM correspondante." : '.';

        $this->info($message);
    }

    /**
     * Factures fournisseurs entrantes.
     *
     * Un document illisible ne bloque pas le flux : il est déposé en boîte de
     * réception avec le statut « unreadable » pour traitement manuel, et le
     * curseur avance. Rien n'est perdu silencieusement, rien ne se coince.
     */
    private function syncInbound(
        PdpCursorSyncGateway&PdpInboundGateway $gateway,
        PdpIncomingInvoiceService $incomingService,
        int $tenantId,
    ): void {
        $entries = $gateway->fetchInbound($tenantId);

        if ($entries === []) {
            $this->line('Réception : aucune nouvelle facture fournisseur.');
            return;
        }

        $ingested = 0;
        $failed   = 0;

        foreach ($entries as $entry) {
            $externalId = $entry['external_id'] ?? null;

            try {
                $incomingService->ingest($entry['content'], $gateway->key(), $externalId);
                $ingested++;
            } catch (\Throwable $e) {
                $failed++;
                Log::error('PdpSync: incoming document unreadable', [
                    'provider'    => $gateway->key(),
                    'external_id' => $externalId,
                    'error'       => $e->getMessage(),
                ]);
                $this->warn("Facture entrante #{$externalId} illisible : {$e->getMessage()}");
                $this->recordUnreadable($gateway->key(), $externalId, $e);
            }

            if ($externalId !== null) {
                $gateway->commitInbound($tenantId, (int) $externalId);
            }
        }

        $this->info("Réception : {$ingested} facture(s) ingérée(s)" . ($failed ? ", {$failed} illisible(s)" : '') . '.');
    }

    /**
     * Trace un document illisible dans la boîte de réception pour qu'il reste
     * visible d'un humain. Idempotent : un même external_id n'est tracé qu'une fois.
     */
    private function recordUnreadable(string $provider, ?string $externalId, \Throwable $e): void
    {
        if ($externalId === null) {
            return;
        }

        $exists = PdpIncomingInvoice::where('provider', $provider)
            ->where('external_id', $externalId)
            ->exists();

        if ($exists) {
            return;
        }

        PdpIncomingInvoice::create([
            'provider'    => $provider,
            'external_id' => $externalId,
            'status'      => PdpIncomingInvoice::STATUS_UNREADABLE,
            'payload'     => ['error' => $e->getMessage()],
        ]);
    }
}
