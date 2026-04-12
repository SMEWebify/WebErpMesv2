<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('audit_plan_id');
            $table->foreign('audit_plan_id')->references('id')->on('audit_plans')->cascadeOnDelete();
            $table->unsignedBigInteger('audit_process_id');
            $table->foreign('audit_process_id')->references('id')->on('audit_processes')->cascadeOnDelete();
            $table->unsignedBigInteger('audit_checklist_id')->nullable(); // FK added after audit_checklists created
            $table->date('planned_date');
            $table->unsignedSmallInteger('planned_duration_hours')->default(2);
            $table->unsignedBigInteger('lead_auditor_id')->nullable();
            $table->foreign('lead_auditor_id')->references('id')->on('users')->nullOnDelete();
            $table->enum('status', ['planned', 'in_progress', 'completed', 'cancelled'])->default('planned');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_schedules');
    }
};
