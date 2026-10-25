<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Tables de l'ancien écran d'inspection remplacées par l'existant :
 * - inspection_documents → la GED (plan de l'article) ;
 * - inspection_instruments → quality_control_devices (avec les étalonnages) ;
 * - inspection_nonconformities → quality_non_conformities.
 *
 * Une table qui contient des lignes n'est PAS supprimée (autre installation qui
 * s'en servait) : elle reste en place, orpheline, et un avertissement le dit.
 * Rien n'est jamais détruit sans transition.
 */
return new class extends Migration
{
    private array $tables = ['inspection_documents', 'inspection_instruments', 'inspection_nonconformities'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $rows = DB::table($table)->count();
            if ($rows > 0) {
                $message = "{$table} conservée : {$rows} ligne(s). À vider à la main une fois les données reprises.";
                Log::warning($message);
                if (app()->runningInConsole()) {
                    fwrite(STDERR, "  ⚠ {$message}\n");
                }
                continue;
            }

            Schema::drop($table);
        }
    }

    /**
     * Pas de retour arrière : les tables ne servaient plus, et les recréer vides
     * ne rendrait aucune donnée.
     */
    public function down(): void
    {
    }
};
