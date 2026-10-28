<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bon pour fabrication (BPF) du bureau d'études, par ligne de commande.
 *
 * Purement additive : null = pas de BPF. Tant que `production.require_release`
 * reste faux (défaut), rien ne lit ces colonnes pour bloquer quoi que ce soit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_lines', function (Blueprint $table) {
            if (! Schema::hasColumn('order_lines', 'released_for_production_at')) {
                $table->timestamp('released_for_production_at')->nullable();
            }
            if (! Schema::hasColumn('order_lines', 'released_for_production_by')) {
                $table->foreignId('released_for_production_by')->nullable()->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_lines', function (Blueprint $table) {
            $table->dropForeign(['released_for_production_by']);
            $table->dropColumn(['released_for_production_at', 'released_for_production_by']);
        });
    }
};
