<?php

namespace App\Console\Commands;

use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompaniesContacts;
use App\Models\User;
use App\Services\Rgpd\RecordRetention;
use App\Services\RgpdAnonymizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeExpiredData extends Command
{
    protected $signature   = 'rgpd:purge';
    protected $description = 'Purge les données personnelles expirées selon les durées de conservation RGPD';

    public function handle(RecordRetention $retention, RgpdAnonymizationService $anonymizer): int
    {
        // 1. Tokens de réinitialisation de mot de passe expirés (> 1h)
        //    Laravel les marque expired mais ne les supprime pas automatiquement.
        $deleted = DB::table('password_resets')
            ->where('created_at', '<', now()->subHour())
            ->delete();
        $this->line("password_resets expirés purgés : {$deleted}");

        // 2. Logs d'emails > 12 mois
        //    Les corps d'emails peuvent contenir des données personnelles.
        $deleted = DB::table('email_logs')
            ->where('created_at', '<', now()->subYear())
            ->delete();
        $this->line("email_logs (> 1 an) purgés : {$deleted}");

        // 3. Enregistrements soft-deleted depuis > 90 jours — suppression définitive
        //    Fenêtre de 90 jours pour permettre une restauration d'urgence.
        //    Sauf ce qu'un enregistrement à conserver cite (ISO 19443, RecordRetention) :
        //    visa, document, NC, dérogation, activité. Ceux-là restent soft-deleted, les
        //    personnes anonymisées : supprimer effacerait l'auteur du visa, voire le
        //    document lui-même (clés en cascade).
        $limit = now()->subDays(90);

        $this->purgeTrashed(CompaniesContacts::class, $limit,
            fn (array $ids) => $retention->retainedContactIds($ids),
            function (CompaniesContacts $contact) use ($anonymizer): bool {
                if ($anonymizer->isAnonymizedContact($contact)) {
                    return false;
                }
                $anonymizer->anonymizeContact($contact);

                return true;
            });

        $this->purgeTrashed(CompaniesAddresses::class, $limit,
            fn (array $ids) => $retention->retainedAddressIds($ids));

        $this->purgeTrashed(Companies::class, $limit,
            fn (array $ids) => $retention->retainedCompanyIds($ids));

        $this->purgeTrashed(User::class, $limit,
            fn (array $ids) => $retention->retainedUserIds($ids),
            function (User $user) use ($anonymizer): bool {
                if ($anonymizer->isAnonymizedUser($user)) {
                    return false;
                }
                $anonymizer->anonymizeUser($user);

                return true;
            });

        $this->info('Purge RGPD terminée.');

        return self::SUCCESS;
    }

    /**
     * Supprime définitivement les enregistrements soft-deleted avant $limit, sauf ceux
     * que $retained garde ; $anonymize (facultatif) est appelé sur chacun de ceux-là
     * et renvoie vrai s'il vient de l'anonymiser.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    private function purgeTrashed(string $model, \DateTimeInterface $limit, \Closure $retained, ?\Closure $anonymize = null): void
    {
        $ids = $model::onlyTrashed()
            ->where('deleted_at', '<', $limit)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $kept = $ids === [] ? [] : $retained($ids);
        $anonymized = 0;

        if ($anonymize !== null && $kept !== []) {
            $model::onlyTrashed()->whereIn('id', $kept)->each(function ($record) use ($anonymize, &$anonymized) {
                $anonymized += $anonymize($record) ? 1 : 0;
            });
        }

        $deleted = 0;
        foreach (array_chunk(array_values(array_diff($ids, $kept)), 500) as $chunk) {
            $deleted += (int) $model::onlyTrashed()->whereIn('id', $chunk)->forceDelete();
        }

        $short = class_basename($model);
        $this->line("{$short} soft-deleted > 90j purgés définitivement : {$deleted}");
        if ($kept !== []) {
            $this->line("{$short} conservés (cités par un enregistrement) : " . count($kept)
                . ($anonymize !== null ? ", dont {$anonymized} anonymisé(s) à l'instant" : ''));
        }
    }
}
