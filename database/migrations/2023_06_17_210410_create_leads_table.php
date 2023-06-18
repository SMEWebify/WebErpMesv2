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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
			$table->integer('companies_id');
			$table->integer('companies_contacts_id');
			$table->integer('companies_addresses_id');
			$table->integer('user_id')->nullable();
            $table->integer('statu')->default(1);
            #1 = New
            #2 = Assigned
            #3 = In progress
            #4 = Converted
            #5 = Lost
            $table->string('source');
            #Web Site
            #Customer referral
            #Trade shows
            #Social media
            #Marketing emails
            #Online advertising campaigns
            #Outbound calls
            #Business partnerships
            #Events and webinars
            #Traditional Advertising
			$table->text('comment', 65535)->nullable();
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
        Schema::dropIfExists('leads');
    }
};
