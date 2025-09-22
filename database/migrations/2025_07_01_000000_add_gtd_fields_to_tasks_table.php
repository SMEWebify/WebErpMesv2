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
            if (!Schema::hasColumn('tasks', 'priority')) {
                $table->unsignedTinyInteger('priority')->default(2)->comment('1: High, 2: Medium, 3: Low');
            }

            if (!Schema::hasColumn('tasks', 'due_date')) {
                $table->date('due_date')->nullable();
            }

            if (!Schema::hasColumn('tasks', 'secondary_user_id')) {
                $table->foreignId('secondary_user_id')->nullable()->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'secondary_user_id')) {
                $table->dropForeign(['secondary_user_id']);
                $table->dropColumn('secondary_user_id');
            }

            if (Schema::hasColumn('tasks', 'due_date')) {
                $table->dropColumn('due_date');
            }

            if (Schema::hasColumn('tasks', 'priority')) {
                $table->dropColumn('priority');
            }
        });
    }
};
