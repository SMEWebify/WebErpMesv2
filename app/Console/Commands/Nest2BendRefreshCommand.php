<?php

namespace App\Console\Commands;

use App\Services\Integrations\Nest2Bend\Nest2BendAccess;
use Illuminate\Console\Command;

/**
 * Relit l'état de l'abonnement NEST2BEND (GET /api/v1/compte) et le garde en base.
 * Planifiée une fois par jour ; sans clé saisie, ne fait rien.
 */
class Nest2BendRefreshCommand extends Command
{
    protected $signature = 'wem:nest2bend:refresh';

    protected $description = "Relit l'état de l'abonnement NEST2BEND (carte Intégrations, boutons Simuler)";

    public function handle(Nest2BendAccess $access): int
    {
        $setting = $access->setting();

        if (! $setting?->hasKey()) {
            $this->info('NEST2BEND : aucune clé saisie.');

            return self::SUCCESS;
        }

        $result = $access->refresh($setting);
        $this->line('NEST2BEND : ' . $access->state($setting->fresh())['status'] . ' — ' . $result['message']);

        return self::SUCCESS;
    }
}
