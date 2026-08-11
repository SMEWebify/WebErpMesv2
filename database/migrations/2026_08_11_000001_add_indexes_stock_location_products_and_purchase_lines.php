<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index de performance complémentaires.
 *
 * stock_location_products n'avait pas d'index sur products_id : toute agrégation
 * de stock par produit (getTotalStockMove, physicalStockOf, /nesting/sheet-stock)
 * scannait la table pour joindre stock_moves.
 *
 * purchase_lines n'avait pas d'index sur product_id : le calcul des quantités en
 * attente de réception par produit faisait un scan complet.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stock_location_products') && ! Schema::hasIndex('stock_location_products', 'stock_location_products_products_id_index')) {
            Schema::table('stock_location_products', function (Blueprint $table) {
                $table->index('products_id');
            });
        }

        if (Schema::hasTable('purchase_lines') && ! Schema::hasIndex('purchase_lines', 'purchase_lines_product_id_index')) {
            Schema::table('purchase_lines', function (Blueprint $table) {
                $table->index('product_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('stock_location_products', 'stock_location_products_products_id_index')) {
            Schema::table('stock_location_products', function (Blueprint $table) {
                $table->dropIndex('stock_location_products_products_id_index');
            });
        }

        if (Schema::hasIndex('purchase_lines', 'purchase_lines_product_id_index')) {
            Schema::table('purchase_lines', function (Blueprint $table) {
                $table->dropIndex('purchase_lines_product_id_index');
            });
        }
    }
};
