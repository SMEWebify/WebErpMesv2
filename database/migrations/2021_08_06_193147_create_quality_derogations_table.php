<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQualityDerogationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quality_derogations', function (Blueprint $table) {
            $table->id();
            $table->string('CODE');
			$table->string('LABEL');
            $table->integer('statu');
			$table->integer('TYPE');
			$table->integer('user_id');
			$table->text('PB_DESCP', 65535);
			$table->text('PROPOSAL', 65535);
			$table->text('REPLY', 65535);
			$table->integer('quality_non_conformitie_id')->nullable();
			$table->text('DECISION', 65535);
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
        Schema::dropIfExists('quality_derogations');
    }
}
