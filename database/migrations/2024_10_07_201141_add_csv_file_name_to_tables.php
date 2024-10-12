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
        Schema::table('quotes', function (Blueprint $table) {
            $table->string('csv_file_name')->nullable()->after('comment'); // Remplacer some_column par la colonne appropriée
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('csv_file_name')->nullable()->after('type'); // Remplacer some_column par la colonne appropriée
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->string('csv_file_name')->nullable()->after('quoted_delivery_note'); // Remplacer some_column par la colonne appropriée
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('csv_file_name')->nullable()->after('svg_file'); // Remplacer some_column par la colonne appropriée
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->string('csv_file_name')->nullable()->after('comment'); // Remplacer some_column par la colonne appropriée
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn('csv_file_name');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('csv_file_name');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('csv_file_name');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('csv_file_name');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('csv_file_name');
        });
    }
};
