<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_findings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('audit_execution_id');
            $table->foreign('audit_execution_id')->references('id')->on('audit_executions')->cascadeOnDelete();
            $table->unsignedBigInteger('audit_checklist_item_id')->nullable();
            $table->foreign('audit_checklist_item_id')->references('id')->on('audit_checklist_items')->nullOnDelete();
            $table->enum('type', ['major_nc', 'minor_nc', 'observation', 'positive_point'])->default('observation');
            $table->text('description');
            $table->text('evidence')->nullable();
            $table->string('iso_clause')->nullable();
            $table->unsignedBigInteger('quality_action_id')->nullable();
            $table->foreign('quality_action_id')->references('id')->on('quality_actions')->nullOnDelete();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_findings');
    }
};
