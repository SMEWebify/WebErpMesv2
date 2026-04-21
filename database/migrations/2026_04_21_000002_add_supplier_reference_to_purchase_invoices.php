<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->string('supplier_reference')->nullable()->after('code');
            // Unicité par fournisseur : empêche la double saisie d'une même facture fournisseur.
            // MySQL autorise plusieurs NULL dans un index unique, donc les factures sans référence ne se bloquent pas entre elles.
            $table->unique(['companies_id', 'supplier_reference'], 'purchase_invoices_company_supplier_ref_unique');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropUnique('purchase_invoices_company_supplier_ref_unique');
            $table->dropColumn('supplier_reference');
        });
    }
};
