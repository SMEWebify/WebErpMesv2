<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notation rapide du fournisseur à la réception (App\Services\Purchases\SupplierReceiptRating).
 *
 * Une note au plus par réception. Les évaluations existantes (par commande d'achat,
 * saisies sur la fiche fournisseur) gardent `purchase_receipt_id` à null et ne
 * changent pas. Une réception supprimée laisse sa note, détachée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_ratings', function (Blueprint $table) {
            $table->unsignedBigInteger('purchase_receipt_id')->nullable()->after('purchases_id');

            $table->unique('purchase_receipt_id');
            $table->foreign('purchase_receipt_id')->references('id')->on('purchase_receipts')->nullOnDelete();
            $table->index(['companies_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('supplier_ratings', function (Blueprint $table) {
            $table->dropForeign(['purchase_receipt_id']);
            $table->dropUnique(['purchase_receipt_id']);
            $table->dropIndex(['companies_id', 'created_at']);
            $table->dropColumn('purchase_receipt_id');
        });
    }
};
