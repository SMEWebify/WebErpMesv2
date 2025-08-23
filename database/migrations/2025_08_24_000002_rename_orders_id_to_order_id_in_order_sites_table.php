<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_sites') && Schema::hasColumn('order_sites', 'orders_id')) {
            Schema::table('order_sites', function (Blueprint $table) {
                $table->renameColumn('orders_id', 'order_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('order_sites') && Schema::hasColumn('order_sites', 'order_id')) {
            Schema::table('order_sites', function (Blueprint $table) {
                $table->renameColumn('order_id', 'orders_id');
            });
        }
    }
};
