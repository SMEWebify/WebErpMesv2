<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quantité traitée par une opération, quand un lot est partagé entre deux
 * machines. Vide = quantité de la ligne, comme aujourd'hui. Ne sert qu'à la
 * durée planifiée : coûts, prix et avancement restent sur la quantité de ligne.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'planned_qty')) {
                $table->decimal('planned_qty', 10, 3)->nullable()->after('qty');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'planned_qty')) {
                $table->dropColumn('planned_qty');
            }
        });
    }
};
