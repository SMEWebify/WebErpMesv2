<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('factory', function (Blueprint $table) {
            $table->boolean('enable_construction_site')->default(false)->after('cgv_file');
        });
    }

    public function down(): void
    {
        Schema::table('factory', function (Blueprint $table) {
            $table->dropColumn('enable_construction_site');
        });
    }
};
