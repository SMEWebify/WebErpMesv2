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
        Schema::table('deliverys', function (Blueprint $table) {
            $table->foreignId('purchases_id')->nullable()->constrained('purchases')->onDelete('set null');
            $table->string('tracking_number')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deliverys', function (Blueprint $table) {
            $table->dropForeign(['purchases_id']);
            $table->dropColumn('purchases_id');
            $table->dropColumn('tracking_number');
        });
    }
};
