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
        Schema::create('osh_risques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('methods_sections')->onDelete('cascade');
            $table->text('description');
            $table->integer('severity')->default(1);
            // 1 = low, 2 = moderate, 3 = high
            $table->integer('probability')->default(1);
            // 1 = rare, 2 = possible, 3 = probable
            $table->text('preventive_measures')->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('osh_risques');
    }
};
