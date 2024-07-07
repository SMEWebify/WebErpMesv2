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
        Schema::create('opportunities_activities_logs', function (Blueprint $table) {
            $table->id();
			$table->integer('opportunities_id');
			$table->string('label');
            $table->integer('type')->default(1);
            #1 = Activity maketing
            #2 = Email Send
            #3 = Pre-sakes activity
            #4 = Sales activity
            #5 = Sales telephone call
            $table->integer('statu')->default(1);
            #1 = no start
            #2 = In progress
            #3 = Closed
            #4 = waiting customer data
            $table->integer('priority')->default(3);
            #1 = Burning
            #2 = Hot
            #3 = Lukewarm 
            #4 = Cold
            $table->date('due_date')->nullable();
			$table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opportunities_activities_logs');
    }
};
