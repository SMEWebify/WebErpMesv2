<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('statuses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug');
            $table->smallInteger('order')->default(0);
        });
    }

    public function down()
    {
        Schema::dropIfExists('statuses');
    }
}
