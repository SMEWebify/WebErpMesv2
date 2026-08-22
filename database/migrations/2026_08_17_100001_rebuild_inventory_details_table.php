<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The 2021 inventory_details schema pointed on stock_locations_id (a bin) and
 * lacked product / lot / serial / quality columns, which is too coarse to
 * carry the Altior-style counting file (one row per product x location x
 * batch x serial x quality). Nothing referenced the table so far, so we
 * rebuild it cleanly rather than patching a dozen ALTER TABLE statements.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('inventory_details');

        Schema::create('inventory_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inventory_id')
                ->constrained('inventories')
                ->cascadeOnDelete();

            $table->foreignId('stock_location_products_id')
                ->constrained('stock_location_products')
                ->cascadeOnDelete();

            $table->foreignId('products_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->foreignId('batch_id')
                ->nullable()
                ->constrained('batches')
                ->nullOnDelete();

            $table->foreignId('serial_number_id')
                ->nullable()
                ->constrained('serial_numbers')
                ->nullOnDelete();

            $table->string('quality', 4)->nullable();

            // Snapshot at inventory creation, never rewritten afterwards.
            $table->decimal('theoretical_qty', 12, 3)->default(0);
            $table->decimal('reserved_qty', 12, 3)->default(0);
            $table->decimal('unit_cost', 12, 4)->default(0);

            // Filled at import from the counting spreadsheet.
            $table->decimal('counted_qty', 12, 3)->nullable();

            $table->foreignId('counted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('counted_at')->nullable();

            // pending | validated | to_check
            $table->string('status', 20)->default('pending');

            $table->text('notes')->nullable();

            // Free-form product properties (length/width for sheets/bars) so a
            // new row created at counting time can carry a different geometry.
            $table->json('properties')->nullable();

            $table->timestamps();

            $table->index(['inventory_id', 'status']);
            $table->index(['inventory_id', 'stock_location_products_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_details');

        // Restore the 2021 shape so a full rollback stays coherent.
        Schema::create('inventory_details', function (Blueprint $table) {
            $table->id();
            $table->integer('inventories_id');
            $table->integer('stock_locations_id');
            $table->decimal('start_qty', 11, 3)->nullable();
            $table->decimal('inv_qty', 11, 3)->nullable();
            $table->decimal('price', 11, 3)->nullable();
            $table->integer('statu')->default(1);
            $table->timestamps();
        });
    }
};
