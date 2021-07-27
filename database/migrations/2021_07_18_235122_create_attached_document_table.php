<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAttachedDocumentTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('attached_document', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->string('LABEL');
			$table->string('PATH_FILE');
			$table->integer('SIZE');
			$table->integer('CREATOR_USER_ID');
			$table->integer('COMPANY_ID')->nullable();
			$table->integer('QUOTE_ID')->nullable();
			$table->integer('ORDER_ID')->nullable();
			$table->integer('ORDER_ACKNOWLEDGMENT_ID')->nullable();
			$table->integer('TASK_ID')->nullable();
			$table->integer('DELIVERY_NOTE_ID')->nullable();
			$table->integer('INVOICE_ID')->nullable();
			$table->integer('DEROGATION_ID')->nullable();
			$table->integer('NC_ID')->nullable();
			$table->integer('ACTION_ID')->nullable();
			$table->integer('MESURING_DEVICE_ID')->nullable();
			$table->integer('COMPONENT_ID')->nullable();
			$table->integer('RESSOURCE_ID')->nullable();
			$table->integer('PURCHASE_REQUEST_ID')->nullable();
			$table->integer('PURCHASE_ORDER_ID')->nullable();
			$table->integer('PURCHASE_DELIVERY_NOTE_ID')->nullable();
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
		Schema::drop('attached_document');
	}

}
