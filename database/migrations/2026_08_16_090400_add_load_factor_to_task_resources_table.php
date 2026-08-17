<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quotité de la capacité de la ressource consommée par la tâche.
 *
 * 1 pour une affectation machine. Pour la main-d'œuvre, c'est le labor_ratio de
 * la machine au moment de l'affectation : un opérateur qui surveille deux
 * machines ne consomme que 0,5 h de capacité humaine par heure machine.
 *
 * Le facteur est figé à l'affectation — c'est la capacité réservée, elle ne doit
 * pas bouger rétroactivement si la machine ou son ratio changent plus tard.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_resources', function (Blueprint $table) {
            $table->decimal('load_factor', 5, 3)->default(1)->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('task_resources', function (Blueprint $table) {
            $table->dropColumn('load_factor');
        });
    }
};
