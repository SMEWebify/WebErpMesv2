<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rotation d'affichage du plan (0, 90, 180, 270° dans le sens horaire), en plus
 * de celle que le PDF déclare lui-même. Les cotes restent stockées dans le
 * repère de la page d'origine : tourner le plan ne les déplace pas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspection_plans', function (Blueprint $table) {
            $table->unsignedSmallInteger('rotation')->default(0)->after('page_count');
        });
    }

    public function down(): void
    {
        Schema::table('inspection_plans', function (Blueprint $table) {
            $table->dropColumn('rotation');
        });
    }
};
