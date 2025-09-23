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
        Schema::table('quotes', function (Blueprint $table) {
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_decision')->nullable();
            $table->foreignId('change_requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('change_reason')->nullable();
            $table->timestamp('change_approved_at')->nullable();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_decision')->nullable();
            $table->foreignId('change_requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('change_reason')->nullable();
            $table->timestamp('change_approved_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropForeign(['change_requested_by']);
            $table->dropColumn([
                'reviewed_by',
                'reviewed_at',
                'review_decision',
                'change_requested_by',
                'change_reason',
                'change_approved_at',
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropForeign(['change_requested_by']);
            $table->dropColumn([
                'reviewed_by',
                'reviewed_at',
                'review_decision',
                'change_requested_by',
                'change_reason',
                'change_approved_at',
            ]);
        });
    }
};
