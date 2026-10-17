<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Liens entre opérations d'une gamme : fin-début (FS), début-début (SS) ou
 * fin-fin (FF), avec un décalage en heures ouvrées ou en pièces.
 *
 * Additive : une opération sans ligne ici garde l'enchaînement historique sur
 * l'opération précédente (`ordre`).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('task_dependencies')) {
            return;
        }

        Schema::create('task_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('predecessor_task_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('type', 2)->default('FS');
            $table->decimal('lag_value', 10, 3)->default(0);
            $table->string('lag_unit', 8)->default('hour');
            $table->timestamps();

            $table->unique(['task_id', 'predecessor_task_id', 'type'], 'task_dependency_unique');
            $table->index('predecessor_task_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_dependencies');
    }
};
