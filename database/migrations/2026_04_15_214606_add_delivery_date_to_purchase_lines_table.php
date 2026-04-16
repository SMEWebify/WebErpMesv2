<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->date('delivery_date')->nullable()->after('invoiced_qty');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->dropColumn('delivery_date');
        });
    }
};
