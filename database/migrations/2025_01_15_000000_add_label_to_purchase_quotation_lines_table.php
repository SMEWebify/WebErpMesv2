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
            $table->string('label')->nullable()->after('tasks_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_quotation_lines', function (Blueprint $table) {
            $table->dropColumn('label');
        });
    }
};
