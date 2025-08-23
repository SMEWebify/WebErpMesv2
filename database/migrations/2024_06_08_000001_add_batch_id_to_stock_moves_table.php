<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_moves', function (Blueprint $table) {
            $table->foreignId('batch_id')->nullable()->after('purchase_receipt_line_id')->constrained('batches');
        });
    }

    public function down(): void
    {
        Schema::table('stock_moves', function (Blueprint $table) {
            $table->dropConstrainedForeignId('batch_id');
        });
    }
};
