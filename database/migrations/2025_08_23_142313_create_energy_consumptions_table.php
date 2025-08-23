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
            $table->date('recorded_at');
            $table->float('amount');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('energy_consumptions');
    }
};
