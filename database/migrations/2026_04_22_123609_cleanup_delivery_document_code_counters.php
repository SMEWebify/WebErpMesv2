<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Supprime les anciens compteurs delivery (global + jours passés) pour que
        // le prochain BL de chaque nouveau jour reparte proprement à 1.
        // Le compteur du jour courant est conservé s'il existe.
        DB::table('document_code_counters')
            ->where('document_type', 'delivery')
            ->where(function ($query) {
                $query->where('period_key', 'global')
                      ->orWhere('period_key', '<', now()->format('Y-m-d'));
            })
            ->delete();
    }

    public function down(): void
    {
        // Pas de rollback possible sur des données supprimées.
    }
};
