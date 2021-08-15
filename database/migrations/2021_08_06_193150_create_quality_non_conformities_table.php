<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQualityNonConformitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quality_non_conformities', function (Blueprint $table) {
            $table->id();
            $table->string('CODE');
			$table->string('LABEL');
			$table->integer('STATU');
			$table->integer('TYPE');
			$table->integer('user_id');
			$table->integer('service_id');
			$table->integer('failure_id');
			$table->text('failure_COMMENT', 65535);
			$table->integer('causes_id');
			$table->text('causes_COMMENT', 65535);
			$table->integer('correction_id');
			$table->text('correction_COMMENT', 65535);
			$table->integer('companie_id');
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
        Schema::dropIfExists('quality_non_conformities');
    }
}
