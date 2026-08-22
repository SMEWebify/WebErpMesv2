<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A sheet-metal product row can hold physical items of different geometries
 * inside the same bin (e.g. a full 1500x3000 sheet plus a 1000x1000 offcut).
 * Snapshotting per (slp, batch) alone would fuse them into a single line,
 * making the count meaningless. We push the geometry down onto every detail
 * so each physical item stays identifiable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_details', function (Blueprint $table) {
            $table->decimal('x_size', 10, 3)->nullable()->after('quality');
            $table->decimal('y_size', 10, 3)->nullable()->after('x_size');
            $table->decimal('z_size', 10, 3)->nullable()->after('y_size');
            $table->integer('nb_part')->nullable()->after('z_size');
            $table->decimal('surface_perc', 6, 3)->nullable()->after('nb_part');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_details', function (Blueprint $table) {
            $table->dropColumn(['x_size', 'y_size', 'z_size', 'nb_part', 'surface_perc']);
        });
    }
};
