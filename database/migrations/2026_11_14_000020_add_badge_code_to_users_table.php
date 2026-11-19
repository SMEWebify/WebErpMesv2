<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Badge opérateur de l'écran de scan (docs/pointage-scan.md) : code imprimé
 * « N2P-B:{badge_code} », scanné au poste avant les opérations. Facultatif, unique.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('badge_code', 64)->nullable()->unique()->after('payroll_number');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['badge_code']);
            $table->dropColumn('badge_code');
        });
    }
};
