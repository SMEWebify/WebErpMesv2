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
            $table->string('code');
			$table->string('label');
			$table->integer('statu');
			$table->integer('type');
			$table->integer('user_id');
			$table->integer('service_id');
			$table->integer('failure_id');
			$table->text('failure_comment', 65535);
			$table->integer('causes_id');
			$table->text('causes_comment', 65535);
			$table->integer('correction_id');
			$table->text('correction_comment', 65535);
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
