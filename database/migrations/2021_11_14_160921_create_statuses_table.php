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
            #Open
            #Started
            #In progress
            #Finished
            #Suspended
            #To RFQ
            #RFQ in progress
            #Outsourced
            #Supplied
            $table->string('title');
            $table->smallInteger('order')->default(0);
        });
    }

    public function down()
    {
        Schema::dropIfExists('statuses');
    }
}
