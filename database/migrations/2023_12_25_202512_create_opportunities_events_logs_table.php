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
        Schema::create('opportunities_events_logs', function (Blueprint $table) {
            $table->id();
			$table->integer('opportunities_id');
			$table->string('label');
            $table->integer('type')->default(1);
            #1 = Activity maketing
            #2 = Internal Meeting
            #3 = Onsite visite
            #4 = Sales Meeting
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
			$table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opportunities_events_logs');
    }
};
