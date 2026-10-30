<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * products-edit : écriture dans la base article (création, modification, fusion,
 * gamme et nomenclature de l'article, fichiers de la GED article, import CSV).
 * products-menu ne donne plus que la lecture.
 *
 * Aucun changement pour l'existant : la permission est donnée au rôle Admin, à
 * tous les rôles qui ont déjà products-menu et aux utilisateurs qui l'ont reçue
 * en direct. Il suffit ensuite de la retirer aux rôles commerciaux.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $edit = Permission::firstOrCreate(['name' => 'products-edit', 'guard_name' => 'web']);
        $menu = Permission::where('name', 'products-menu')->where('guard_name', 'web')->first();

        $roles = Role::where('guard_name', 'web')
            ->where(fn ($q) => $q->where('name', 'Admin')
                ->when($menu, fn ($q) => $q->orWhereHas('permissions', fn ($p) => $p->whereKey($menu->id))))
            ->get();

        foreach ($roles as $role) {
            if (! $role->hasPermissionTo($edit)) {
                $role->givePermissionTo($edit);
            }
        }

        if ($menu) {
            $direct = DB::table('model_has_permissions')->where('permission_id', $menu->id)->get();

            foreach ($direct as $row) {
                DB::table('model_has_permissions')->insertOrIgnore([
                    'permission_id' => $edit->id,
                    'model_type' => $row->model_type,
                    'model_id' => $row->model_id,
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Retirer la permission rendrait la base article non modifiable pour tous
        // les rôles sauf l'administrateur : on la garde.
    }
};
