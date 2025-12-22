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
        Schema::table('companies_contacts', function (Blueprint $table) {
            $table->string('password')->nullable()->after('default');
            $table->rememberToken()->nullable()->after('password');
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies_contacts', function (Blueprint $table) {
            $table->dropColumn(['password', 'remember_token', 'last_login_at']);
        });
    }
};
