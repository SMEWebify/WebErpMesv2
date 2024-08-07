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
			$table->unsignedBigInteger('companies_id');
			$table->unsignedBigInteger('companies_contacts_id');
			$table->unsignedBigInteger('companies_addresses_id');
			$table->unsignedBigInteger('user_id')->nullable();
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
            $table->integer('priority')->default(3);
            #1 = Burning
            #2 = Hot
            #3 = Lukewarm 
            #4 = Cold
            $table->string('campaign')->nullable();
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
        Schema::dropIfExists('leads');
    }
};
