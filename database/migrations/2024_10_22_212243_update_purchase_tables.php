<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Ajouter accounting_allocation_id à la table purchase_invoice_lines
        Schema::table('purchase_invoice_lines', function (Blueprint $table) {
            $table->integer('accounting_allocation_id')->nullable()->after('purchase_line_id'); // Remplace 'some_other_column' par la colonne après laquelle tu veux l'ajouter
        });

        // Supprimer accounting_allocation_id de la table purchase_lines
        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->dropColumn('accounting_allocation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Supprimer accounting_allocation_id de purchase_invoice_lines si la migration est annulée
        Schema::table('purchase_invoice_lines', function (Blueprint $table) {
            $table->dropColumn('accounting_allocation_id');
        });

        // Ajouter à nouveau accounting_allocation_id à purchase_lines si la migration est annulée
        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->integer('accounting_allocation_id')->nullable();
        });
    }
};
