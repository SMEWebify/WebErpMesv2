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
        Schema::create('task_resources', function (Blueprint $table) {
            $table->id();
            $table->integer('task_id');
            $table->integer('methods_ressources_id');
            $table->integer('autoselected_ressource')->default(0);
            #1 = resource selected by automatic scheduling 
            #0 = resource not selected by automatic scheduling
            $table->integer('userforced_ressource')->default(0);
            #1 = resource forced manualy by user in the planning for the task
            #0 = resource not forced manualy by user in the planning for the task
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
