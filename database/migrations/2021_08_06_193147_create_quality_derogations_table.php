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
			$table->integer('CREATOR_ID');
			$table->integer('TYPE');
			$table->integer('ETAT');
			$table->integer('RESP_ID');
			$table->text('PB_DESCP', 65535);
			$table->text('PROPOSAL', 65535);
			$table->integer('REPLY');
			$table->text('COMMENT', 65535);
			$table->integer('NFC_ID');
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
