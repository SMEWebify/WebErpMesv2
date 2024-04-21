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
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('name');
            $table->string('original_file_name');
            $table->string('type');
            $table->string('size');
            $table->integer('companies_id')->nullable();
            $table->integer('opportunities_id')->nullable();
            $table->integer('quotes_id')->nullable();
            $table->integer('orders_id')->nullable();
            $table->integer('deliverys_id')->nullable();
            $table->integer('invoices_id')->nullable();
            $table->integer('products_id')->nullable();
            $table->integer('purchases_id')->nullable();
            $table->integer('purchase_receipts_id')->nullable();
            $table->integer('quality_non_conformities_id')->nullable();
            $table->boolean('as_photo')->default(false);
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
        Schema::dropIfExists('files');
    }
};
