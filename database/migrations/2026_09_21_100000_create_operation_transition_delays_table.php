<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operation_transition_delays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_service_id')->constrained('methods_services')->cascadeOnDelete();
            $table->foreignId('to_service_id')->constrained('methods_services')->cascadeOnDelete();
            $table->decimal('transfer_hours', 8, 2)->default(0);
            $table->timestamps();

            $table->unique(['from_service_id', 'to_service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operation_transition_delays');
    }
};
