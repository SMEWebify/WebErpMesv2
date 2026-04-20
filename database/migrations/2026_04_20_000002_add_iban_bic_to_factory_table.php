<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('factory', function (Blueprint $table) {
            $table->string('iban')->nullable()->after('share_capital');
            $table->string('bic')->nullable()->after('iban');
        });
    }

    public function down(): void
    {
        Schema::table('factory', function (Blueprint $table) {
            $table->dropColumn(['iban', 'bic']);
        });
    }
};
