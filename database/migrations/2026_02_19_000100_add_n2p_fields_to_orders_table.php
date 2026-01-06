<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dateTime('n2p_last_push_at')->nullable()->after('type');
            $table->string('n2p_last_push_status')->nullable()->after('n2p_last_push_at');
            $table->text('n2p_last_push_error')->nullable()->after('n2p_last_push_status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['n2p_last_push_at', 'n2p_last_push_status', 'n2p_last_push_error']);
        });
    }
};
