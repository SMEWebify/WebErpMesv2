<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * workshop-kiosk : compte de poste atelier partagé, enfermé sur l'écran de scan,
 * qui exige un badge avant tout pointage. Donnée à personne (pas même Admin :
 * elle restreint) ; à mettre sur un rôle dédié « Poste atelier ».
 */
return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'workshop-kiosk', 'guard_name' => 'web']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Permission conservée : elle a pu être donnée à un rôle entre-temps.
    }
};
