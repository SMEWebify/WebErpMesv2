<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMethodsToolsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('methods_tools', function (Blueprint $table) {
            $table->id();
            $table->string('code');
			$table->string('label')->nullable();
			$table->integer('ETAT');
            $table->decimal('cost', 11, 3)->nullable();
			$table->string('picture')->nullable();
			$table->date('end_date')->nullable();
			$table->text('comment')->nullable();
			$table->integer('qty');
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
        Schema::dropIfExists('methods_tools');
    }
}
