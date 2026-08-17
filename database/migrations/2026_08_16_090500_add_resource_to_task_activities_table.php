<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rattache le temps déclaré à la ressource qui l'a réalisé.
 *
 * task_activities ne portait que user_id : impossible de calculer une charge
 * réelle ou un TRS par machine, et surtout l'historique se réécrivait tout seul
 * — le réalisé était lu à travers l'affectation *courante* de la tâche, donc
 * changer de machine après coup déplaçait rétroactivement les heures passées.
 *
 * La colonne est nullable : les déclarations faites sur une tâche non affectée
 * restent valides, simplement non imputées à une ressource.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_activities', function (Blueprint $table) {
            $table->foreignId('methods_ressources_id')
                ->nullable()
                ->after('task_id')
                ->constrained('methods_ressources')
                ->nullOnDelete();
        });

        $this->backfill();
    }

    /**
     * Reprise de l'existant : à défaut de mieux, on impute les déclarations
     * passées à l'affectation actuelle de la tâche — machine en priorité, sinon
     * main-d'œuvre. Approximatif pour l'historique déjà réaffecté, mais c'est la
     * seule information disponible, et à partir d'ici le rattachement est figé.
     *
     * Groupé par ressource puis découpé en paquets d'ids : quelques UPDATE au
     * total au lieu d'un par tâche, et aucune requête spécifique à un SGBD.
     */
    private function backfill(): void
    {
        $tasksByResource = [];

        DB::table('task_resources')
            ->orderBy('id')
            ->select(['task_id', 'methods_ressources_id', 'role'])
            ->get()
            ->groupBy('task_id')
            ->each(function ($rows, $taskId) use (&$tasksByResource) {
                $row = $rows->firstWhere('role', 'machine') ?? $rows->first();
                $tasksByResource[$row->methods_ressources_id][] = $taskId;
            });

        foreach ($tasksByResource as $resourceId => $taskIds) {
            foreach (array_chunk($taskIds, 500) as $chunk) {
                DB::table('task_activities')
                    ->whereIn('task_id', $chunk)
                    ->update(['methods_ressources_id' => $resourceId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('task_activities', function (Blueprint $table) {
            $table->dropForeign(['methods_ressources_id']);
            $table->dropColumn('methods_ressources_id');
        });
    }
};
