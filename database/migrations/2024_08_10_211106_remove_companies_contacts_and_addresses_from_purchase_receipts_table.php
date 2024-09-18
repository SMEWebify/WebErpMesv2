<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
/**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchase_receipts', function (Blueprint $table) {
            // Supprime les colonnes
            $table->dropColumn('companies_contacts_id');
            $table->dropColumn('companies_addresses_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchase_receipts', function (Blueprint $table) {
            // Réintroduit les colonnes
            $table->unsignedBigInteger('companies_contacts_id');
            $table->unsignedBigInteger('companies_addresses_id');
});
    }
};
