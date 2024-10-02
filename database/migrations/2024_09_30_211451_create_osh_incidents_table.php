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
        Schema::create('osh_incidents', function (Blueprint $table) {
            $table->id();
            $table->date('incident_date');
            $table->text('description');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('severity')->default(1);
            // 1 = minor, 2 = major, 3 = critical
            $table->text('corrective_actions')->nullable();
            $table->integer('statut')->default(1);
           // 1 = in progress, 2 = resolved
            $table->date('resolution_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('osh_incidents');
    }
};
