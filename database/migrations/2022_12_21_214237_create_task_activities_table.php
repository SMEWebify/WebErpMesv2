<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task_activities', function (Blueprint $table) {
            $table->id();
            $table->integer('task_id');
			$table->integer('user_id');
            $table->integer('type');
            #1 = Start time
            #2 = End time
            #3 = Finish Task
            #4 = Declare a finished part quantity
            #5 = Declare a refuse  part quantity
            $table->timestamp('timestamp');
            $table->integer('good_qt')->default(0);
			$table->integer('bad_qt')->default(0);
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
        Schema::dropIfExists('task_activities');
    }
};
