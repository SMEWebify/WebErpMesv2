<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies_addresses', function (Blueprint $table) {
            $table->string('province')->nullable()->after('city');
        });
    }

    public function down(): void
    {
        Schema::table('companies_addresses', function (Blueprint $table) {
            $table->dropColumn('province');
        });
    }
};