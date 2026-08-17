<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reconstruit task_resources :
 *  - vraies clés étrangères (les colonnes étaient des `integer` libres, sans FK,
 *    donc des lignes orphelines pouvaient survivre à la suppression d'une tâche) ;
 *  - unicité (task_id, methods_ressources_id, role) — le code fait partout
 *    `$task->resources->first()`, la table doit refléter cette hypothèse ;
 *  - `role` : prépare la ressource main-d'œuvre (une tâche = 1 machine + 1 MO) ;
 *  - `source` : remplace les deux entiers autoselected_ressource /
 *    userforced_ressource, incohérents (le job d'affectation écrivait 0/0 alors
 *    que 1 devait signifier « choisi par l'ordonnancement »), donc impossibles
 *    à interpréter.
 *
 * La table est recréée puis recopiée plutôt qu'altérée : `change()` + ajout de FK
 * a posteriori ne fonctionne pas de la même façon sur SQLite (tests) et MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('task_resources', 'task_resources_old');

        Schema::create('task_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('methods_ressources_id')->constrained('methods_ressources')->cascadeOnDelete();
            // machine = capacité machine, labor = capacité main-d'œuvre (lot 2).
            $table->enum('role', ['machine', 'labor'])->default('machine');
            // auto = ordonnancement, manual = choix utilisateur, forced = forçage planning.
            $table->enum('source', ['auto', 'manual', 'forced'])->default('auto');
            $table->timestamps();

            $table->unique(['task_id', 'methods_ressources_id', 'role'], 'task_resource_role_unique');
            $table->index('methods_ressources_id');
        });

        $this->copyFromLegacyTable();

        Schema::drop('task_resources_old');
    }

    /**
     * Recopie en écartant :
     *  - les orphelins (tâche ou ressource disparue) que l'absence de FK laissait passer ;
     *  - les doublons (task, ressource) : on conserve la ligne forcée la plus récente.
     */
    private function copyFromLegacyTable(): void
    {
        DB::table('task_resources_old as tr')
            ->join('tasks', 'tasks.id', '=', 'tr.task_id')
            ->join('methods_ressources as r', 'r.id', '=', 'tr.methods_ressources_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('task_resources_old as dup')
                    ->whereColumn('dup.task_id', 'tr.task_id')
                    ->whereColumn('dup.methods_ressources_id', 'tr.methods_ressources_id')
                    ->where(function ($better) {
                        $better->whereColumn('dup.userforced_ressource', '>', 'tr.userforced_ressource')
                            ->orWhere(function ($tie) {
                                $tie->whereColumn('dup.userforced_ressource', '=', 'tr.userforced_ressource')
                                    ->whereColumn('dup.id', '>', 'tr.id');
                            });
                    });
            })
            ->orderBy('tr.id')
            ->select([
                'tr.task_id',
                'tr.methods_ressources_id',
                'tr.userforced_ressource',
                'tr.created_at',
                'tr.updated_at',
            ])
            ->chunk(500, function ($rows) {
                DB::table('task_resources')->insertOrIgnore(
                    $rows->map(fn ($row) => [
                        'task_id'               => $row->task_id,
                        'methods_ressources_id' => $row->methods_ressources_id,
                        'role'                  => 'machine',
                        'source'                => (int) $row->userforced_ressource === 1 ? 'forced' : 'auto',
                        'created_at'            => $row->created_at,
                        'updated_at'            => $row->updated_at,
                    ])->all()
                );
            });
    }

    public function down(): void
    {
        Schema::rename('task_resources', 'task_resources_new');

        Schema::create('task_resources', function (Blueprint $table) {
            $table->id();
            $table->integer('task_id');
            $table->integer('methods_ressources_id');
            $table->integer('autoselected_ressource')->default(0);
            $table->integer('userforced_ressource')->default(0);
            $table->timestamps();
        });

        DB::table('task_resources_new')
            ->orderBy('id')
            ->select(['task_id', 'methods_ressources_id', 'source', 'created_at', 'updated_at'])
            ->chunk(500, function ($rows) {
                DB::table('task_resources')->insert(
                    $rows->map(fn ($row) => [
                        'task_id'                => $row->task_id,
                        'methods_ressources_id'  => $row->methods_ressources_id,
                        'autoselected_ressource' => 0,
                        'userforced_ressource'   => $row->source === 'forced' ? 1 : 0,
                        'created_at'             => $row->created_at,
                        'updated_at'             => $row->updated_at,
                    ])->all()
                );
            });

        Schema::drop('task_resources_new');
    }
};
