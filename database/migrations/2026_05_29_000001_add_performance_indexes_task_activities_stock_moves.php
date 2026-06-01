<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index de performance critiques.
 *
 * task_activities n'avait aucun index sur task_id : les SUM(TIMESTAMPDIFF(...))
 * par tâche (Task::getTotalLogStartTime/EndTime, progress(), TotalTime()) faisaient
 * un scan complet de la table à chaque appel, en boucle sur chaque tâche d'une
 * commande. Mesure : fiche commande de >60 s à ~3 s après ajout de l'index.
 *
 * stock_moves n'avait pas d'index sur stock_location_products_id : les calculs
 * d'entrées/sorties de stock et de CUMP scannaient toute la table par emplacement.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('task_activities') && ! $this->indexExists('task_activities', 'task_activities_task_id_type_index')) {
            Schema::table('task_activities', function (Blueprint $table) {
                $table->index(['task_id', 'type']);
            });
        }

        if (Schema::hasTable('stock_moves') && ! $this->indexExists('stock_moves', 'stock_moves_stock_location_products_id_typ_move_index')) {
            Schema::table('stock_moves', function (Blueprint $table) {
                $table->index(['stock_location_products_id', 'typ_move']);
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('task_activities', 'task_activities_task_id_type_index')) {
            Schema::table('task_activities', function (Blueprint $table) {
                $table->dropIndex('task_activities_task_id_type_index');
            });
        }

        if ($this->indexExists('stock_moves', 'stock_moves_stock_location_products_id_typ_move_index')) {
            Schema::table('stock_moves', function (Blueprint $table) {
                $table->dropIndex('stock_moves_stock_location_products_id_typ_move_index');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return Schema::hasIndex($table, $index);
    }
};
