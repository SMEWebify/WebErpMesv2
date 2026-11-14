<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Codes de scan N2P:{of}*{ligne}*{op}*{seq} sur la fiche suiveuse (docs/pointage-scan.md).
 * Faux par défaut : la fiche reste celle d'avant tant que la société ne l'active pas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('factory', function (Blueprint $table) {
            $table->boolean('production_sheet_scan_codes')->default(false)->after('task_barre_code');
        });
    }

    public function down(): void
    {
        Schema::table('factory', function (Blueprint $table) {
            $table->dropColumn('production_sheet_scan_codes');
        });
    }
};
