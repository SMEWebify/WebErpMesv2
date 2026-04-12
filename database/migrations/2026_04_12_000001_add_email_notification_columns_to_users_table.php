<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('companies_email_notification')->default(false)->after('companies_notification');
            $table->boolean('users_email_notification')->default(false)->after('users_notification');
            $table->boolean('quotes_email_notification')->default(false)->after('quotes_notification');
            $table->boolean('orders_email_notification')->default(false)->after('orders_notification');
            $table->boolean('non_conformity_email_notification')->default(false)->after('non_conformity_notification');
            $table->boolean('return_email_notification')->default(false)->after('return_notification');
            $table->boolean('pre_order_email_notification')->default(false)->after('pre_order_notification');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'companies_email_notification',
                'users_email_notification',
                'quotes_email_notification',
                'orders_email_notification',
                'non_conformity_email_notification',
                'return_email_notification',
                'pre_order_email_notification',
            ]);
        });
    }
};
