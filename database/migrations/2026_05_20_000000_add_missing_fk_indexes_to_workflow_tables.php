<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->index('companies_id');
            $table->index('user_id');
            $table->index('statu');
            $table->index('opportunities_id');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->index('companies_id');
            $table->index('user_id');
            $table->index('statu');
        });

        Schema::table('deliverys', function (Blueprint $table) {
            $table->index('companies_id');
            $table->index('user_id');
        });

        Schema::table('delivery_lines', function (Blueprint $table) {
            $table->index('deliverys_id');
            $table->index('order_line_id');
        });

        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->index('invoices_id');
            $table->index('order_line_id');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropIndex(['companies_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['statu']);
            $table->dropIndex(['opportunities_id']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['companies_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['statu']);
        });

        Schema::table('deliverys', function (Blueprint $table) {
            $table->dropIndex(['companies_id']);
            $table->dropIndex(['user_id']);
        });

        Schema::table('delivery_lines', function (Blueprint $table) {
            $table->dropIndex(['deliverys_id']);
            $table->dropIndex(['order_line_id']);
        });

        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->dropIndex(['invoices_id']);
            $table->dropIndex(['order_line_id']);
        });
    }
};
