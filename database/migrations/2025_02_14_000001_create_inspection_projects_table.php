<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInspectionProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inspection_projects', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('title');
            $table->unsignedBigInteger('companies_id');
            $table->unsignedBigInteger('orders_id')->nullable();
            $table->unsignedBigInteger('order_lines_id')->nullable();
            $table->unsignedBigInteger('of_id')->nullable();
            $table->string('status')->default('draft');
            $table->integer('quantity_planned')->nullable();
            $table->boolean('serial_tracking')->default(false);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index(['companies_id', 'orders_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inspection_projects');
    }
}
