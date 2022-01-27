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
            $table->string('code');
			$table->string('label');
            $table->integer('statu');
			$table->integer('type');
			$table->integer('user_id');
			$table->text('pb_descp', 65535);
			$table->text('proposal', 65535);
			$table->text('reply', 65535);
			$table->integer('quality_non_conformitie_id')->nullable();
			$table->text('decision', 65535);
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
