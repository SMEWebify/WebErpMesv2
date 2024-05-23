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
        Schema::table('stock_moves', function (Blueprint $table) {
            $table->decimal('x_size', 20, 8)->nullable()->after('company_id');
            $table->decimal('y_size', 20, 8)->nullable()->after('x_size');
            $table->decimal('z_size', 20, 8)->nullable()->after('y_size');
            $table->integer('nb_part')->nullable()->after('z_size');
            $table->decimal('surface_perc', 20, 8)->nullable()->after('nb_part');
            $table->string('nest_path')->nullable()->after('surface_perc');
            $table->string('nest_picture_path')->nullable()->after('nest_path');
            $table->tinyInteger('status')->default(1)->after('nest_picture_path');
            $table->string('tracability')->nullable()->after('status');
        });
    }

    public function down()
    {
        Schema::table('stock_moves', function (Blueprint $table) {
            $table->dropColumn('x_size');
            $table->dropColumn('y_size');
            $table->dropColumn('z_size');
            $table->dropColumn('nb_part');
            $table->dropColumn('surface_perc');
            $table->dropColumn('nest_path');
            $table->dropColumn('nest_picture_path');
            $table->dropColumn('status');
            $table->dropColumn('tracability');
        });
    }
};
