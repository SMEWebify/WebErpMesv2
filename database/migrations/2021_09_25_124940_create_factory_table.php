<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFactoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('factory', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Name of site');
			$table->string('address')->default('Address');
			$table->string('city')->default('City');
			$table->string('zipcode')->default('Zip code');
			$table->string('region')->nullable();
			$table->string('country')->nullable();
			$table->string('phone_number')->nullable();
			$table->string('mail')->nullable();
			$table->string('web_site')->nullable();
			$table->string('pdf_header_font_color')->default('#60A7A6');
			$table->string('picture')->nullable();
			$table->string('siren')->nullable();
			$table->string('nat_regis_num')->nullable();
			$table->string('vat_num')->nullable();
			$table->integer('accounting_vats_id')->default(1);
			$table->string('share_capital')->nullable();
            $table->string('curency')->nullable();
            $table->string('add_day_validity_quote')->nullable()->default(0);
            $table->string('add_delivery_delay_order')->nullable()->default(0);
			$table->string('task_barre_code')->default('EAN13');
			$table->integer('public_link_cgv')->default(1);
			$table->integer('add_cgv_to_pdf')->default(1);
			$table->string('cgv_file')->nullable();
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
        Schema::dropIfExists('factory');
    }
}
