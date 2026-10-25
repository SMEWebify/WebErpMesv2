<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Relie l'écriture d'un avoir (journal VENT, montants inversés) à sa ligne d'avoir, pour
 * l'annuler quand l'avoir est refusé et la rétablir s'il est rouvert. Additive et nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounting_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('credit_note_line_id')->nullable()->index()->after('invoice_payment_id');
        });
    }

    public function down(): void
    {
        Schema::table('accounting_entries', function (Blueprint $table) {
            $table->dropIndex(['credit_note_line_id']);
            $table->dropColumn('credit_note_line_id');
        });
    }
};
