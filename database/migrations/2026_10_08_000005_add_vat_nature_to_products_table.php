<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nature de TVA de l'article, saisie dans la fiche article à côté de la famille.
 * Sert d'axe à la matrice de TVA et de levier d'imputation analytique.
 *
 * Nullable : les articles existants restent sans nature → la ligne retombe sur
 * le code de TVA par défaut (comportement actuel). Rétrocompatible. Les lignes
 * libres sans article portent leur nature au niveau ligne (override).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('vat_nature_id')->nullable()->after('methods_families_id')
                ->constrained('vat_natures')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vat_nature_id');
        });
    }
};
