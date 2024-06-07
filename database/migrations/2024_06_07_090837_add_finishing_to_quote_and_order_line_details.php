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
            $table->string('finishing')->nullable()->after('thickness');
        });

        Schema::table('order_line_details', function (Blueprint $table) {
            $table->string('finishing')->nullable()->after('thickness');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quote_line_details', function (Blueprint $table) {
            $table->dropColumn('finishing');
        });

        Schema::table('order_line_details', function (Blueprint $table) {
            $table->dropColumn('finishing');
        });
    }
};
