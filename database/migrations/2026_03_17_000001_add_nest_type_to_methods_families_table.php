<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('methods_families', function (Blueprint $table) {
            $table->enum('nest_type', ['sheet', 'bar'])->nullable()->after('methods_services_id');
        });
    }

    public function down(): void
    {
        Schema::table('methods_families', function (Blueprint $table) {
            $table->dropColumn('nest_type');
        });
    }
};
