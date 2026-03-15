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
        Schema::table('orders', function (Blueprint $table) {
            $table->index('statu');
        });

        Schema::table('order_lines', function (Blueprint $table) {
            $table->index('orders_id');
            $table->index('delivery_status');
            $table->index('tasks_status');
        });

        Schema::table('quote_lines', function (Blueprint $table) {
            $table->index('quotes_id');
            $table->index('statu');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->index('order_lines_id');
            $table->index('quote_lines_id');
            $table->index('status_id');
            $table->index('products_id');
        });

        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->index('receipt_qty');
            $table->index('invoiced_qty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['statu']);
        });

        Schema::table('order_lines', function (Blueprint $table) {
            $table->dropIndex(['orders_id']);
            $table->dropIndex(['delivery_status']);
            $table->dropIndex(['tasks_status']);
        });

        Schema::table('quote_lines', function (Blueprint $table) {
            $table->dropIndex(['quotes_id']);
            $table->dropIndex(['statu']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['order_lines_id']);
            $table->dropIndex(['quote_lines_id']);
            $table->dropIndex(['status_id']);
            $table->dropIndex(['products_id']);
        });

        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->dropIndex(['receipt_qty']);
            $table->dropIndex(['invoiced_qty']);
        });
    }
};
