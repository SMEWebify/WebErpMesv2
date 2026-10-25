<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Qui a besoin d'un plan de contrôle : le client l'exige (companies), l'article
 * peut forcer dans un sens ou dans l'autre (products : null = suit le client,
 * always, never). Additive, sans effet tant que rien n'est coché.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('requires_control_plan')->default(false);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('control_plan_mode', 10)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('requires_control_plan');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('control_plan_mode');
        });
    }
};
