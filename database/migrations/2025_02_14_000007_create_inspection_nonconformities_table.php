<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInspectionNonconformitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inspection_nonconformities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inspection_project_id');
            $table->unsignedBigInteger('inspection_measure_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('open');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index(['inspection_project_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inspection_nonconformities');
    }
}
