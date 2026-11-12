<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Poste de pliage (presse plieuse), sur le modèle de `is_nesting`. Faux pour l'existant.
 * Prépare l'écriture du temps NEST2BEND dans la gamme ; aujourd'hui ce temps ne va que
 * dans le commentaire interne de la ligne.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('methods_services', function (Blueprint $table) {
            $table->boolean('is_bending')->default(false)->after('is_nesting');
        });
    }

    public function down(): void
    {
        Schema::table('methods_services', function (Blueprint $table) {
            $table->dropColumn('is_bending');
        });
    }
};
