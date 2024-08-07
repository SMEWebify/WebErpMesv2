<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliverysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deliverys', function (Blueprint $table) {
            $table->id();
            $table->string('code');
			$table->string('label');
			$table->unsignedBigInteger('companies_id');
			$table->unsignedBigInteger('companies_contacts_id');
			$table->unsignedBigInteger('companies_addresses_id');
			$table->integer('statu')->default(1);
            #1 = In progress
            #2 = Sent
            $table->integer('invoice_status')->default(1);
            #1 = Chargeable
            #2 = Not chargeable
            #3 = Partly invoiced
            #4 = Invoiced
			$table->unsignedBigInteger('user_id');
			$table->text('comment')->nullable();
            $table->integer('order_id')->nullable();
            $table->timestamps();

            // Foreign Key Constraints
            $table->foreign('companies_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('companies_contacts_id')->references('id')->on('companies_contacts')->onDelete('cascade');
            $table->foreign('companies_addresses_id')->references('id')->on('companies_addresses')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deliverys');
    }
}
