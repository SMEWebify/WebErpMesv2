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
        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'code')) {
                $table->string('code')->nullable()->after('id');
            }

            if (!Schema::hasColumn('tasks', 'start_date')) {
                $table->dateTime('start_date')->nullable()->after('to_schedule');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'start_date')) {
                $table->dropColumn('start_date');
            }

            if (Schema::hasColumn('tasks', 'code')) {
                $table->dropColumn('code');
            }
        });
    }
};
