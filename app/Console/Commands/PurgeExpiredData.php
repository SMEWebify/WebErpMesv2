<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeExpiredData extends Command
{
    protected $signature   = 'rgpd:purge';
    protected $description = 'Purge les données personnelles expirées selon les durées de conservation RGPD';

    public function handle(): int
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
        foreach ([
            \App\Models\Companies\CompaniesContacts::class,
            \App\Models\Companies\CompaniesAddresses::class,
            \App\Models\Companies\Companies::class,
            \App\Models\User::class,
        ] as $model) {
            $count = $model::onlyTrashed()
                ->where('deleted_at', '<', now()->subDays(90))
                ->forceDelete();
            $short = class_basename($model);
            $this->line("{$short} soft-deleted > 90j purgés définitivement : {$count}");
        }

        $this->info('Purge RGPD terminée.');

        return self::SUCCESS;
    }
}
