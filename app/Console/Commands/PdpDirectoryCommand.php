<?php

namespace App\Console\Commands;

use App\Services\Integrations\Pdp\Contracts\PdpDirectoryGateway;
use App\Services\Integrations\Pdp\PdpManager;
use Illuminate\Console\Command;

/**
 * Annuaire de la facturation électronique.
 *
 * Sert deux besoins distincts :
 *
 *  - **Ouvrir notre ligne de réception** (`--open`). Sans elle, aucune facture
 *    fournisseur ne peut nous parvenir : c'est le prérequis de l'obligation de
 *    réception au 1er septembre 2026, et seule une Plateforme Agréée peut
 *    l'ouvrir pour le compte d'une société.
 *
 *  - **Trouver l'adresse d'un client** (`--lookup`, `--search`), à reporter sur
 *    sa fiche société dans `companies.electronic_address`.
 */
class PdpDirectoryCommand extends Command
{
    protected $signature = 'wem:pdp:directory
                            {--open= : Ouvre une ligne d\'annuaire (SIREN, SIREN_SUFFIXE ou adresse Peppol)}
                            {--date= : Date de prise d\'effet AAAA-MM-JJ (annuaire français, ex. 2026-09-01)}
                            {--close= : Ferme la ligne d\'annuaire portant cet identifiant}
                            {--lookup= : Adresses de facturation d\'une entreprise, par SIREN}
                            {--search= : Recherche une entreprise par début de raison sociale}';

    protected $description = "Consulte l'annuaire de la facturation électronique et gère les lignes de réception";

    public function handle(PdpManager $manager): int
    {
        $gateway = $manager->driver();

        if (! $gateway->isEnabled()) {
            $this->warn("Le driver PDP [{$gateway->key()}] n'est pas configuré.");
            return self::FAILURE;
        }

        if (! $gateway instanceof PdpDirectoryGateway) {
            $this->warn("Le driver PDP [{$gateway->key()}] n'expose pas l'annuaire.");
            return self::FAILURE;
        }

        try {
            return match (true) {
                (bool) $this->option('open')   => $this->open($gateway),
                (bool) $this->option('close')  => $this->close($gateway),
                (bool) $this->option('lookup') => $this->lookup($gateway),
                (bool) $this->option('search') => $this->search($gateway),
                default                        => $this->list($gateway),
            };
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }

    /** Nos lignes de réception. */
    private function list(PdpDirectoryGateway $gateway): int
    {
        $entries = $gateway->listEntries();

        if ($entries === []) {
            $this->warn('Aucune ligne d\'annuaire ouverte : aucune facture fournisseur ne peut vous parvenir.');
            $this->line('  Ouvrez-en une avec : php artisan wem:pdp:directory --open=VOTRE_SIREN');
            return self::SUCCESS;
        }

        $this->table(
            ['Id', 'Identifiant', 'Annuaire', 'Prise d\'effet', 'Retour technique'],
            array_map(fn (array $e) => [
                $e['id'],
                $e['identifier'],
                $e['directory'],
                $e['effective_date'] ?? '—',
                // Adresse de retour ouverte par la plateforme pour recevoir les
                // messages de cycle de vie ; elle ne reçoit pas de factures.
                $e['is_replyto'] ? 'oui' : '',
            ], $entries)
        );

        return self::SUCCESS;
    }

    private function open(PdpDirectoryGateway $gateway): int
    {
        $identifier = (string) $this->option('open');
        $date       = $this->option('date') ? (string) $this->option('date') : null;

        $entry = $gateway->openEntry($identifier, $date);

        $this->info("Ligne ouverte : {$entry['identifier']} (annuaire {$entry['directory']}, id {$entry['id']}).");
        $this->line('Les factures fournisseurs adressées à cette adresse seront désormais remises à votre plateforme,');
        $this->line('puis importées dans WEM par « php artisan wem:pdp:sync ».');

        return self::SUCCESS;
    }

    private function close(PdpDirectoryGateway $gateway): int
    {
        $id = (string) $this->option('close');

        if (! $this->confirm("Fermer la ligne d'annuaire {$id} ? Les factures adressées à cette adresse ne seront plus reçues.", false)) {
            $this->line('Annulé.');
            return self::SUCCESS;
        }

        $gateway->closeEntry($id);
        $this->info("Ligne {$id} fermée.");

        return self::SUCCESS;
    }

    /** Adresses d'un client, à reporter sur sa fiche société. */
    private function lookup(PdpDirectoryGateway $gateway): int
    {
        $siren   = (string) $this->option('lookup');
        $entries = $gateway->lookupEntries($siren);

        if ($entries === []) {
            $this->warn("Aucune adresse de facturation pour le SIREN {$siren}.");
            $this->line("Ce client n'est pas encore inscrit à l'annuaire : demandez-lui son adresse, ou réessayez plus tard.");
            return self::SUCCESS;
        }

        $this->table(
            ['Identifiant', 'Active', 'Raison sociale', 'Ville'],
            array_map(fn (array $e) => [
                $e['identifier'],
                $e['is_active'] ? 'oui' : 'non',
                $e['name'] ?? '',
                $e['city'] ?? '',
            ], $entries)
        );

        $this->line('À reporter dans « Adresse électronique » sur la fiche société du client');
        $this->line('(la partie après « 0225: »).');

        return self::SUCCESS;
    }

    private function search(PdpDirectoryGateway $gateway): int
    {
        $companies = $gateway->searchCompanies(['name' => (string) $this->option('search')]);

        if ($companies === []) {
            $this->warn('Aucune entreprise trouvée.');
            return self::SUCCESS;
        }

        $this->table(
            ['SIREN', 'Raison sociale', 'Code postal', 'Ville'],
            array_map(fn (array $c) => [
                $c['number'],
                $c['formal_name'],
                $c['postcode'] ?? '',
                $c['city'] ?? '',
            ], $companies)
        );

        return self::SUCCESS;
    }
}
