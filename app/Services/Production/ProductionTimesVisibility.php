<?php

namespace App\Services\Production;

use App\Models\User;

/**
 * Temps et avancement de l'écran tâche (factory.hide_production_times).
 *
 * Option active : l'opérateur ne voit ni temps prévus, ni temps pointé, ni TRS,
 * ni avancement en temps — pour ne pas presser ni ralentir la cadence. Le
 * pointage est inchangé : les temps restent enregistrés (coûts, TRS, rapports,
 * paie). Un porteur de `production-times-view` (chef d'atelier, méthodes) voit tout.
 */
class ProductionTimesVisibility
{
    public const PERMISSION = 'production-times-view';

    public function hiddenFor(?User $user): bool
    {
        if (! optional(app('Factory'))->hide_production_times) {
            return false;
        }

        return ! ($user && $user->can(self::PERMISSION));
    }
}
