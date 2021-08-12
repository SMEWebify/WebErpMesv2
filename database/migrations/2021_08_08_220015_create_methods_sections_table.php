<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMethodsSectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('methods_sections', function (Blueprint $table) {
            $table->id();
            $table->integer('ORDRE');
			$table->string('CODE');
			$table->string('LABEL');
			$table->integer('user_id');
			$table->string('COLOR');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('methods_sections');
    }
}
