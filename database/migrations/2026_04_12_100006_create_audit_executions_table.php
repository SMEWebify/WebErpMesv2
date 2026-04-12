<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_executions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('audit_schedule_id');
            $table->foreign('audit_schedule_id')->references('id')->on('audit_schedules')->cascadeOnDelete();
            $table->date('actual_date');
            $table->unsignedSmallInteger('actual_duration_hours')->nullable();
            $table->text('summary')->nullable();
            $table->text('conclusion')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_executions');
    }
};
