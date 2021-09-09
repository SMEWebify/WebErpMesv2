<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMethodsRessourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('methods_ressources', function (Blueprint $table) {
            $table->id();
            $table->integer('ORDRE');
            $table->string('CODE');
			$table->string('LABEL');
			$table->string('PICTURE')->nullable();
			$table->integer('MASK_TIME');
			$table->decimal('CAPACITY', 11, 3);
			$table->integer('section_id');
			$table->string('COLOR');
			$table->integer('service_id');
			$table->text('COMMENT', 65535)->nullable();
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
        Schema::dropIfExists('methods_ressources');
    }
}
