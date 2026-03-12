<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pre_order_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('suggested_product_id')->nullable()->after('total_price');
            $table->unsignedBigInteger('linked_product_id')->nullable()->after('suggested_product_id');
            $table->decimal('matching_unit_price', 12, 3)->nullable()->after('linked_product_id');
            $table->timestamp('matching_confirmed_at')->nullable()->after('matching_unit_price');

            $table->foreign('suggested_product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreign('linked_product_id')->references('id')->on('products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pre_order_lines', function (Blueprint $table) {
            $table->dropForeign(['suggested_product_id']);
            $table->dropForeign(['linked_product_id']);
            $table->dropColumn(['suggested_product_id', 'linked_product_id', 'matching_unit_price', 'matching_confirmed_at']);
        });
    }
};
