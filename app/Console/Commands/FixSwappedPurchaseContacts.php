<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Reprise des commandes d'achat et demandes de prix créées depuis l'écran
 * « Demande d'achat » : l'adresse et le contact par défaut y étaient passés
 * dans le mauvais ordre, d'où un id d'adresse rangé dans
 * companies_contacts_id et un id de contact dans companies_addresses_id.
 *
 * Un document n'est corrigé que si l'inversion est certaine : ni le contact
 * ni l'adresse stockés n'appartiennent au fournisseur, alors que les deux
 * valeurs inversées lui appartiennent. Un document à moitié cohérent (corrigé
 * à la main depuis, par exemple) est seulement signalé.
 */
class FixSwappedPurchaseContacts extends Command
{
    protected $signature = 'wem:purchases:fix-swapped-contacts {--dry-run : Show what would change without saving}';

    protected $description = 'Swap back companies_contacts_id / companies_addresses_id on purchase orders and quotations created with them inverted';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // Appartenance, soft-deleted compris : seul le rattachement compte ici.
        $contacts  = DB::table('companies_contacts')->pluck('companies_id', 'id');
        $addresses = DB::table('companies_addresses')->pluck('companies_id', 'id');

        $rows = [];
        $fixed = 0;
        $ambiguous = 0;

        foreach (['purchases', 'purchases_quotations'] as $table) {
            $documents = DB::table($table)
                ->select(['id', 'code', 'companies_id', 'companies_contacts_id', 'companies_addresses_id'])
                ->orderBy('id')
                ->get();

            foreach ($documents as $document) {
                $company = (int) $document->companies_id;
                $contactId = $document->companies_contacts_id;
                $addressId = $document->companies_addresses_id;

                $contactOk = $contactId !== null && (int) ($contacts[$contactId] ?? 0) === $company;
                $addressOk = $addressId !== null && (int) ($addresses[$addressId] ?? 0) === $company;

                if ($contactOk && $addressOk) {
                    continue;
                }

                $swappedOk = $addressId !== null && $contactId !== null
                    && (int) ($contacts[$addressId] ?? 0) === $company
                    && (int) ($addresses[$contactId] ?? 0) === $company;

                if (!$swappedOk) {
                    continue;
                }

                if ($contactOk || $addressOk) {
                    $ambiguous++;
                    $rows[] = [$table, $document->id, $document->code, $contactId, $addressId, 'ambiguous, left as is'];
                    continue;
                }

                $fixed++;
                $rows[] = [$table, $document->id, $document->code, $contactId, $addressId, 'swapped'];

                if (!$dryRun) {
                    DB::table($table)->where('id', $document->id)->update([
                        'companies_contacts_id' => $addressId,
                        'companies_addresses_id' => $contactId,
                    ]);
                }
            }
        }

        if ($rows && $this->output->isVerbose()) {
            $this->table(['Table', 'Id', 'Code', 'Contact id', 'Address id', 'Action'], $rows);
        }

        $this->info(sprintf(
            '%d document(s) %s, %d ambiguous left as is.%s',
            $fixed,
            $dryRun ? 'would be fixed (dry run, nothing saved)' : 'fixed',
            $ambiguous,
            $this->output->isVerbose() ? '' : ' Use -v to list them.'
        ));

        return self::SUCCESS;
    }
}
