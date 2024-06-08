<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->integer('client_type')->default(1)->after('code');
            // 1 = 'société' or 2 = 'particulier'
            $table->string('civility')->nullable()->after('client_type');
            $table->string('last_name')->nullable()->after('label');
        });
    }

    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('client_type');
            $table->dropColumn('civility');
            $table->dropColumn('last_name');
        });
    }
};
