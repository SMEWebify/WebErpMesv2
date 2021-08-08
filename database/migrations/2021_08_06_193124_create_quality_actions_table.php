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
			$table->integer('CREATOR_ID');
			$table->text('TYPE');
			$table->text('ETAT');
			$table->text('PB_DESCP', 65535)->nullable();
			$table->text('CAUSE', 65535)->nullable();
			$table->text('ACTION', 65535)->nullable();
			$table->string('COLOR');
			$table->integer('NFC_ID')->nullable();
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
