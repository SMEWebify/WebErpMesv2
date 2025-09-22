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
        Schema::create('return_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('return_id');
            $table->unsignedBigInteger('delivery_line_id')->nullable();
            $table->unsignedBigInteger('original_task_id')->nullable();
            $table->unsignedBigInteger('rework_task_id')->nullable();
            $table->integer('qty')->nullable();
            $table->text('issue_description')->nullable();
            $table->text('rework_instructions')->nullable();
            $table->timestamps();

            $table->foreign('return_id')->references('id')->on('returns')->cascadeOnDelete();
            $table->foreign('delivery_line_id')->references('id')->on('delivery_lines')->nullOnDelete();
            $table->foreign('original_task_id')->references('id')->on('tasks')->nullOnDelete();
            $table->foreign('rework_task_id')->references('id')->on('tasks')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_lines');
    }
};
