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
            $table->string('code');
			$table->string('label');
            $table->text('statu');
            $table->text('type');
			$table->integer('user_id');
			$table->text('pb_descp')->nullable();
			$table->text('cause')->nullable();
			$table->text('action')->nullable();
			$table->string('color');
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
