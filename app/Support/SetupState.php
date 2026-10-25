<?php

namespace App\Support;

use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentConditions;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingVat;
use App\Models\Admin\EstimatedBudgets;
use App\Models\Admin\Factory;
use App\Models\Methods\MethodsUnits;
use Illuminate\Support\Facades\DB;

/**
 * Où en est l'installation initiale (assistant /setup).
 *
 * Les étapes 1 à 6 remplissent les six référentiels exigés par CheckFactory ; les étapes 7
 * (rôle) et 8 (objectif de chiffre) viennent après. Chaque groupe se ferme pour son compte :
 * - référentiels : dès qu'ils sont remplis (ne pas réécrire la société) ;
 * - rôle : dès qu'un utilisateur a un rôle — sinon n'importe quel compte pourrait s'attribuer
 *   toutes les permissions ;
 * - objectif : dès qu'un objectif existe (la suite se gère dans l'écran d'administration).
 */
class SetupState
{
    public static function referentialsDone(): bool
    {
        return Factory::exists()
            && AccountingVat::exists()
            && AccountingPaymentConditions::exists()
            && AccountingPaymentMethod::exists()
            && AccountingDelivery::exists()
            && MethodsUnits::exists();
    }

    public static function anyUserHasRole(): bool
    {
        return DB::table(config('permission.table_names.model_has_roles'))->exists();
    }

    public static function budgetDone(): bool
    {
        return EstimatedBudgets::exists();
    }

    public static function finished(): bool
    {
        return self::referentialsDone() && self::anyUserHasRole() && self::budgetDone();
    }
}
