<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('qonto_connections', function (Blueprint $table) {
            $table->boolean('import_bidirectionnel')->default(false)->after('iban');
        });
    }

    public function down(): void
    {
        Schema::table('qonto_connections', function (Blueprint $table) {
            $table->dropColumn('import_bidirectionnel');
        });
    }
};
