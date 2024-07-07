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
            #1 => In Progress
            #2 => Wainting customer data
            #3 => Validate
            #4 => Canceled
			$table->integer('type');
            #1 => Internal
            #2 => External
			$table->integer('user_id');
			$table->integer('service_id')->nullable();
			$table->integer('failure_id')->nullable();
			$table->text('failure_comment')->nullable();
			$table->integer('causes_id')->nullable();
			$table->text('causes_comment')->nullable();
			$table->integer('correction_id')->nullable();
			$table->text('correction_comment')->nullable();
			$table->integer('companie_id')->nullable();
			$table->integer('order_lines_id')->nullable();
			$table->integer('task_id')->nullable();
			$table->integer('qty')->nullable();
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
