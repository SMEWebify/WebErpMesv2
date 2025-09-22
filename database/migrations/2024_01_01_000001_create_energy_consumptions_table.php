<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('energy_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('methods_ressource_id')->constrained('methods_ressources')->cascadeOnDelete();
            $table->decimal('kwh', 10, 2);
            $table->decimal('cost_per_kwh', 10, 2);
            $table->decimal('total_cost', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('energy_consumptions');
    }
};

