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
        Schema::table('quote_lines', function (Blueprint $table) {
            $table->boolean('use_calculated_price')->default(false)->after('statu');
        });
        Schema::table('order_lines', function (Blueprint $table) {
            $table->boolean('use_calculated_price')->default(false)->after('invoice_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quote_lines', function (Blueprint $table) {
            $table->dropColumn('use_calculated_price');
        });

        Schema::table('order_lines', function (Blueprint $table) {
            $table->dropColumn('use_calculated_price');
        });
    }
};
