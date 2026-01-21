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
        Schema::table('maintenance_work_orders', function (Blueprint $table) {
            $table->string('work_type')->default('preventive')->after('priority');
            $table->foreignId('assigned_to')->nullable()->after('work_type')->constrained('users')->nullOnDelete();
            $table->dateTime('started_at')->nullable()->after('scheduled_at');
            $table->dateTime('finished_at')->nullable()->after('started_at');
            $table->unsignedInteger('estimated_duration_minutes')->nullable()->after('finished_at');
            $table->unsignedInteger('actual_duration_minutes')->nullable()->after('estimated_duration_minutes');
            $table->text('actions_performed')->nullable()->after('description');
            $table->text('parts_consumed')->nullable()->after('actions_performed');
            $table->text('comments')->nullable()->after('parts_consumed');
            $table->string('failure_type')->nullable()->after('comments');
            $table->string('severity')->nullable()->after('failure_type');
            $table->boolean('machine_stopped')->default(false)->after('severity');
            $table->dateTime('failure_started_at')->nullable()->after('machine_stopped');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenance_work_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropColumn([
                'work_type',
                'started_at',
                'finished_at',
                'estimated_duration_minutes',
                'actual_duration_minutes',
                'actions_performed',
                'parts_consumed',
                'comments',
                'failure_type',
                'severity',
                'machine_stopped',
                'failure_started_at',
            ]);
        });
    }
};
