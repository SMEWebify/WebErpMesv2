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
        Schema::table('purchase_receipt_lines', function (Blueprint $table) {
            $table->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('inspection_date')->nullable();
            $table->integer('accepted_qty')->default(0);
            $table->integer('rejected_qty')->default(0);
            $table->string('inspection_result')->nullable();
            $table->foreignId('quality_non_conformity_id')->nullable()->constrained('quality_non_conformities')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_receipt_lines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('quality_non_conformity_id');
            $table->dropColumn('inspection_result');
            $table->dropColumn('rejected_qty');
            $table->dropColumn('accepted_qty');
            $table->dropColumn('inspection_date');
            $table->dropConstrainedForeignId('inspected_by');
        });
    }
};
