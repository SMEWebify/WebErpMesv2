<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Référence de l'article chez le fournisseur (code catalogue), par couple
 * (article, fournisseur) : un même article a une référence différente chez
 * chacun de ses fournisseurs. Additive et nullable : rien ne change pour l'existant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products_preferred_suppliers', function (Blueprint $table) {
            $table->string('supplier_reference', 100)->nullable()->after('companies_id');
            // Recherche d'un article par la référence d'un fournisseur donné
            $table->index(['companies_id', 'supplier_reference'], 'pps_company_supplier_reference_index');
        });
    }

    public function down(): void
    {
        Schema::table('products_preferred_suppliers', function (Blueprint $table) {
            $table->dropIndex('pps_company_supplier_reference_index');
            $table->dropColumn('supplier_reference');
        });
    }
};
