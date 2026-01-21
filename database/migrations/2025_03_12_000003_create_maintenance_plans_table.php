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
        Schema::create('maintenance_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('trigger_type');
            $table->string('trigger_value')->nullable();
            $table->date('fixed_date')->nullable();
            $table->unsignedInteger('estimated_duration_minutes')->nullable();
            $table->string('required_skill')->nullable();
            $table->text('actions')->nullable();
            $table->text('required_parts')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_plans');
    }
};
