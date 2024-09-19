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
        Schema::table('companies', function (Blueprint $table) {
            $table->string('longitude')->nullable();
            $table->string('latitude')->nullable();
            $table->integer('delivery_constraint')->default(1);
            #1 - No constraints
            #2 - No tolerance
            #3 - Tolerance expressed in days: (number of days) tolerance_days
            $table->integer('tolerance_days')->default(0);
            $table->boolean('quoted_delivery_note')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            
            $table->dropColumn('longitude');
            $table->dropColumn('latitude');
            $table->dropColumn('delivery_constraint');
            $table->dropColumn(columns: 'tolerance_days');
            $table->dropColumn('quoted_delivery_note');
        });
    }
};
