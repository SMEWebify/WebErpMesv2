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
            $table->foreignId('machine_id')->constrained('methods_ressources');
            $table->float('kwh_consumed');
            $table->decimal('cost', 10, 2);
            $table->timestamp('recorded_at');
            $table->float('amount');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('energy_consumptions');
    }
};
