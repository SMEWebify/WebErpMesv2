<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Traceability link back to the inventory that generated an entry (typ_move=1)
 * or a shortage (typ_move=15). Lets the timeline UI show which counting file
 * produced a given regularisation move.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_moves', function (Blueprint $table) {
            $table->foreignId('inventory_id')
                ->nullable()
                ->after('purchase_receipt_line_id')
                ->constrained('inventories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_moves', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inventory_id');
        });
    }
};
