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
        Schema::create('order_line_details', function (Blueprint $table) {
            $table->id();
            $table->integer('order_lines_id');
            $table->decimal('x_size', 10, 3)->nullable();
			$table->decimal('y_size', 10, 3)->nullable();
			$table->decimal('z_size', 10, 3)->nullable();
			$table->decimal('x_oversize', 10, 3)->nullable();
			$table->decimal('y_oversize', 10, 3)->nullable();
			$table->decimal('z_oversize', 10, 3)->nullable();
            $table->decimal('diameter', 10, 3)->nullable();
			$table->decimal('diameter_oversize', 10, 3)->nullable();
			$table->string('material')->nullable();
			$table->decimal('thickness', 10, 3)->nullable();
			$table->decimal('weight', 10, 3)->nullable();
			$table->decimal('material_loss_rate', 10, 3)->nullable();
            $table->string('cad_file')->nullable();
            $table->string('picture')->nullable();
			$table->text('internal_comment')->nullable();
			$table->text('external_comment')->nullable();
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
        Schema::dropIfExists('order_line_details');
    }
};
