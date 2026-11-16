<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Déduction d'acompte : ligne négative d'une facture qui reprend une ligne de
 * facture d'acompte (invoice_type 4). Nul pour toutes les lignes existantes.
 * Le reste à déduire d'un acompte n'est jamais stocké : il se relit sur ces lignes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('down_payment_line_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->dropIndex(['down_payment_line_id']);
            $table->dropColumn('down_payment_line_id');
        });
    }
};
