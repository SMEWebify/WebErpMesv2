<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_sites', function (Blueprint $table) {
            if (!Schema::hasColumn('order_sites', 'label')) {
                $table->string('label')->nullable()->after('order_id');
            }
        });

        if (Schema::hasColumn('order_sites', 'label') && Schema::hasColumn('order_sites', 'name')) {
            DB::table('order_sites')
                ->whereNull('label')
                ->whereNotNull('name')
                ->update(['label' => DB::raw('name')]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_sites', function (Blueprint $table) {
            if (Schema::hasColumn('order_sites', 'label')) {
                $table->dropColumn('label');
            }
        });
    }
};
