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
        Schema::table('quality_non_conformities', function (Blueprint $table) {
            
            $table->integer('deliverys_id')->nullable()->after('order_lines_id');
            $table->integer('delivery_line_id')->nullable()->after('deliverys_id');
            $table->date('resolution_date')->nullable()->after('qty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quality_non_conformities', function (Blueprint $table) {
            $table->dropColumn('deliverys_id');
            $table->dropColumn('delivery_line_id');
            $table->dropColumn('resolution_date');
        });
    }
};
