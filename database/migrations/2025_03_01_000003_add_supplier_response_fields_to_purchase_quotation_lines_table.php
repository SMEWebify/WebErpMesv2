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
        Schema::table('purchase_quotation_lines', function (Blueprint $table) {
            $table->unsignedInteger('lead_time_days')->nullable()->after('total_price');
            $table->text('conditions')->nullable()->after('lead_time_days');
            $table->decimal('supplier_score', 8, 2)->nullable()->after('conditions');
            $table->text('supplier_comment')->nullable()->after('supplier_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_quotation_lines', function (Blueprint $table) {
            $table->dropColumn(['lead_time_days', 'conditions', 'supplier_score', 'supplier_comment']);
        });
    }
};
