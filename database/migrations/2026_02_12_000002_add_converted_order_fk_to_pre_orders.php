<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pre_orders', function (Blueprint $table) {
            $table->foreign('converted_order_id')
                ->references('id')
                ->on('orders')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pre_orders', function (Blueprint $table) {
            $table->dropForeign(['converted_order_id']);
        });
    }
};
