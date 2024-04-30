<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('image_url')->nullable();
            $table->string('personnal_phone_number')->nullable();
            $table->string('born_date')->nullable();
            $table->string('desc')->nullable();

            /** Add for WebErpMesv2/issues/142 */
            $table->string('nationality')->nullable();
            $table->integer('gender')->nullable();
            #1 => Male
            #2 => Female
            #3 => Other
            $table->integer('marital_status')->nullable();
            #1 => Married
            #2 => Single
            #3 => Divorced
            #3 => Widowed
            #3 => Other
            $table->string('ssn_num')->nullable();
            $table->string('nic_num')->nullable();
            $table->string('driving_license')->nullable();
            $table->date('driving_license_exp_date')->nullable();
            $table->integer('employment_status')->default(1);
            #1 => Undefined
            #2 => worker
            #3 => Employee
            #4 => Self-employed
            $table->string('job_title')->nullable();
            $table->string('pay_grade', 10, 3)->nullable();
            $table->string('work_station_id')->nullable();
            $table->string('address1')->nullable();
            $table->string('address2')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('home_phone')->nullable();
            $table->string('mobile_phone')->nullable();
            $table->string('private_email')->nullable();
            $table->date('joined_date')->nullable();
            $table->date('confirmation_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->integer('supervisor_id')->nullable();
            $table->integer('section_id')->nullable();
            $table->string('custom1')->nullable();
            $table->string('custom2')->nullable();
            $table->string('custom3')->nullable();
            $table->string('custom4')->nullable();
            $table->integer('statu')->default(1);
            #1 => Active
            #2 => Inactive
            /** end add for WebErpMesv2/issues/142 */

            $table->boolean('companies_notification')->default(1);
            $table->boolean('users_notification')->default(1);
            $table->boolean('quotes_notification')->default(1);
            $table->boolean('orders_notification')->default(1);
            $table->timestamp('last_seen')->nullable();
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
