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
        Schema::create('serial_number_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_serial_id')->constrained('serial_numbers');
            $table->foreignId('component_serial_id')->constrained('serial_numbers');
            $table->foreignId('task_id')->constrained('tasks');
            $table->timestamps();
            $table->unique('component_serial_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('serial_number_components');
    }
};
