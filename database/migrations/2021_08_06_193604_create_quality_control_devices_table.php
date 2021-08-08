<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQualityControlDevicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quality_control_devices', function (Blueprint $table) {
            $table->id();
            $table->string('CODE');
			$table->string('LABEL');
			$table->integer('RESSOURCE_ID');
			$table->integer('USER_ID');
			$table->string('SERIAL_NUMBER');
			$table->string('PICTURE_DEVICES');
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
        Schema::dropIfExists('quality_control_devices');
    }
}
