<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mode de l'écran de scan atelier (docs/pointage-scan.md) : open (le scan ouvre la
 * tâche), toggle (1er scan = début, 2e = fin), toggle_qty (fin avec quantités).
 * « open » par défaut : le scan se comporte comme la recherche de l'écran tâche.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('factory', function (Blueprint $table) {
            $table->string('scan_mode', 16)->default('open')->after('production_sheet_scan_codes');
        });
    }

    public function down(): void
    {
        Schema::table('factory', function (Blueprint $table) {
            $table->dropColumn('scan_mode');
        });
    }
};
