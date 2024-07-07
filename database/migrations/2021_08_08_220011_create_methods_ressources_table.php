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
            $table->integer('ordre');
            $table->string('code');
			$table->string('label');
			$table->string('picture')->nullable();
			$table->integer('mask_time');
			$table->decimal('capacity', 11, 3);
			$table->integer('section_id');
			$table->string('color');
			$table->integer('methods_services_id');
			$table->text('comment')->nullable();
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
