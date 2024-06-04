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
        Schema::table('invoices', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
            $table->date('payment_date')->nullable()->after('order_id');
            $table->date('due_date')->nullable()->after('payment_date');
            $table->date('export_date')->nullable()->after('due_date');
            $table->string('incoterm')->nullable()->after('export_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['payment_date', 'due_date', 'export_date', 'incoterm', 'uuid']);
        });
    }
};
