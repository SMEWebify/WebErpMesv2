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
        Schema::table('dashboard_configs', function (Blueprint $table) {
            $table->json('today_layout')->nullable()->after('layout');
            $table->string('home_view', 20)->default('kpi')->after('today_layout');
        });
    }

    public function down(): void
    {
        Schema::table('dashboard_configs', function (Blueprint $table) {
            $table->dropColumn(['today_layout', 'home_view']);
        });
    }
};
