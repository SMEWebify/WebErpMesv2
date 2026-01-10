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
        Schema::table('order_site_implantations', function (Blueprint $table) {
            if (!Schema::hasColumn('order_site_implantations', 'order_sites_id')) {
                $table->unsignedBigInteger('order_sites_id')->nullable()->after('order_site_id');
            }
        });

        if (Schema::hasColumn('order_site_implantations', 'order_sites_id')) {
            DB::table('order_site_implantations')
                ->whereNull('order_sites_id')
                ->update(['order_sites_id' => DB::raw('order_site_id')]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_site_implantations', function (Blueprint $table) {
            if (Schema::hasColumn('order_site_implantations', 'order_sites_id')) {
                $table->dropColumn('order_sites_id');
            }
        });
    }
};
