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
        Schema::create('user_employment_contracts', function (Blueprint $table) {
            $table->id();
			$table->integer('user_id');
            $table->integer('statu')->default(1);
            #1 = On trial
            #2 = Asset
            #3 = Closed
			$table->integer('methods_section_id');
            $table->date('signature_date')->nullable();
            $table->string('type_of_contract')->nullable();
            $table->date('start_date')->nullable();
			$table->integer('duration_trial_period');
            $table->date('end_date')->nullable();
			$table->integer('weekly_duration')->default(0);
            $table->string('position')->nullable();
            $table->string('coefficient')->nullable();
            $table->decimal('hourly_gross_salary', 10, 3)->nullable();
			$table->integer('minimum_monthly_salary')->default(0);
			$table->integer('annual_gross_salary')->default(0);
            $table->string('end_of_contract_reason')->nullable();
            $table->string('coment')->nullable();
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
        Schema::dropIfExists('user_employment_contracts');
    }
};
