<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_lines', function (Blueprint $table) {
            // Snapshot du prix au moment de la facturation.
            // Rend la facture immutable vis-à-vis des modifications ultérieures de la ligne de commande.
            $table->decimal('unit_price', 15, 4)->nullable()->after('qty');
            $table->decimal('discount',    5, 2)->nullable()->after('unit_price');
            $table->decimal('vat_rate',    5, 2)->nullable()->after('discount');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->dropColumn(['unit_price', 'discount', 'vat_rate']);
        });
    }
};
