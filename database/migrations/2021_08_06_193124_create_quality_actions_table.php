<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQualityActionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quality_actions', function (Blueprint $table) {
            $table->id();
            $table->string('CODE');
			$table->string('LABEL');
            $table->text('STATU');
            $table->text('TYPE');
			$table->integer('user_id');
			$table->text('PB_DESCP', 65535)->nullable();
			$table->text('CAUSE', 65535)->nullable();
			$table->text('ACTION', 65535)->nullable();
			$table->string('COLOR');
			$table->integer('quality_non_conformitie_id')->nullable();
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
        Schema::dropIfExists('quality_actions');
    }
}
