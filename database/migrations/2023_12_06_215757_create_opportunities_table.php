<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
			$table->unsignedBigInteger('companies_id');
			$table->unsignedBigInteger('companies_contacts_id');
			$table->unsignedBigInteger('companies_addresses_id');
			$table->unsignedBigInteger('user_id')->nullable();
			$table->integer('leads_id')->nullable();
			$table->string('label');
			$table->decimal('budget', 10, 3);
			$table->date('close_date')->nullable();
            $table->integer('statu')->default(1);
            #1 = New
            #2 = Quote made
            #3 = Negotiation
            #4 = Closed-won
            #5 = Closed-lost
            #6 = Informational
            $table->integer('probality')->default(50);
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
     */
    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
