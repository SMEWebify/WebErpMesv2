<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * days_count is the decimal cost of the absence, resolved once on save by
 * LeaveBalanceService (weekends and bank holidays already removed) so that a
 * balance never has to expand every date range at read time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('times_absences', function (Blueprint $table) {
            $table->foreignId('leave_type_id')->nullable()->after('user_id')
                ->constrained('leave_types')->nullOnDelete();
            $table->decimal('days_count', 6, 2)->default(0)->after('absence_type_day');
            $table->decimal('hours_count', 6, 2)->nullable()->after('days_count');
            $table->string('comment')->nullable()->after('end_date');
            $table->index(['user_id', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::table('times_absences', function (Blueprint $table) {
            $table->dropForeign(['leave_type_id']);
            $table->dropIndex(['user_id', 'start_date']);
            $table->dropColumn(['leave_type_id', 'days_count', 'hours_count', 'comment']);
        });
    }
};
