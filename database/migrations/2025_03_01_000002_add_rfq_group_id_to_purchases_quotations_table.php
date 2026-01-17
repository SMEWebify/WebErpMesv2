<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchases_quotations', function (Blueprint $table) {
            $table->unsignedBigInteger('rfq_group_id')->nullable()->after('companies_addresses_id');
            $table->index('rfq_group_id', 'purchases_quotations_rfq_group_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases_quotations', function (Blueprint $table) {
            $table->dropIndex('purchases_quotations_rfq_group_id_index');
            $table->dropColumn('rfq_group_id');
        });
    }
};
