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
        Schema::table('quote_line_details', function (Blueprint $table) {
            $table->unsignedInteger('bend_count')->nullable()->after('weight');
        });

        Schema::table('order_line_details', function (Blueprint $table) {
            $table->unsignedInteger('bend_count')->nullable()->after('weight');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quote_line_details', function (Blueprint $table) {
            $table->dropColumn('bend_count');
        });

        Schema::table('order_line_details', function (Blueprint $table) {
            $table->dropColumn('bend_count');
        });
    }
};
