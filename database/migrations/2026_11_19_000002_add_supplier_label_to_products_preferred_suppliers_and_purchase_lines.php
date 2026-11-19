<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Désignation de l'article chez le fournisseur, à côté de sa référence
 * (products_preferred_suppliers.supplier_reference), et sa copie sur la ligne de
 * commande d'achat. Additive et nullable : commandes et PDF existants inchangés.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products_preferred_suppliers', function (Blueprint $table) {
            $table->string('supplier_label')->nullable()->after('supplier_reference');
        });

        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->string('supplier_label')->nullable()->after('supplier_ref');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->dropColumn('supplier_label');
        });

        Schema::table('products_preferred_suppliers', function (Blueprint $table) {
            $table->dropColumn('supplier_label');
        });
    }
};
