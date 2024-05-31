<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('supplier_ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchases_id');
            $table->unsignedBigInteger('companies_id');
            $table->unsignedTinyInteger('rating')->comment('Rating from 1 to 5');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->foreign('purchases_id')->references('id')->on('purchases')->onDelete('cascade');
            $table->foreign('companies_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('supplier_ratings');
    }
};
