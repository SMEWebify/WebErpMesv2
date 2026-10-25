<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Relie une écriture d'encaissement (journal ENCAIS) au règlement qui l'a produite, pour
 * l'effacer ou la contre-passer quand le règlement est supprimé. Additive et nullable :
 * les écritures existantes restent telles quelles (retrouvées par leurs références).
 * Pas de clé étrangère : le règlement est supprimé, la contre-passation reste.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounting_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('invoice_payment_id')->nullable()->index()->after('invoice_line_id');
        });
    }

    public function down(): void
    {
        Schema::table('accounting_entries', function (Blueprint $table) {
            $table->dropIndex(['invoice_payment_id']);
            $table->dropColumn('invoice_payment_id');
        });
    }
};
