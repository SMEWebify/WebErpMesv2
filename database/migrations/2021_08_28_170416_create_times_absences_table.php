<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTimesAbsencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('times_absences', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
			$table->integer('absence_type');
            $table->integer('absence_type_day');
            $table->integer('statu');
			$table->date('START_DATE');
			$table->date('END_DATE');
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
        Schema::dropIfExists('times_absences');
    }
}
