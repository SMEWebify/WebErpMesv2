<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchasesQuotationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchases_quotations', function (Blueprint $table) {
            $table->id();
            $table->string('code');
			$table->string('label');
			$table->unsignedBigInteger('companies_id');
			$table->unsignedBigInteger('companies_contacts_id');
			$table->unsignedBigInteger('companies_addresses_id');
			$table->date('delay')->nullable();
			$table->integer('statu')->default(1);
            #1 = In progress
            #2 = Sent
            #3 = Partly received
            #4 = Received
            #5 = PO partly created
            #6 = PO Created
			$table->unsignedBigInteger('user_id');
			$table->text('comment')->nullable();
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
        Schema::dropIfExists('purchases_quotations');
    }
}
