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
        Schema::table('methods_services', function (Blueprint $table) {
            $table->renameColumn('compannie_id', 'companies_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('methods_services', function (Blueprint $table) {
            $table->renameColumn('companies_id', 'compannie_id');
        });
    }
};
