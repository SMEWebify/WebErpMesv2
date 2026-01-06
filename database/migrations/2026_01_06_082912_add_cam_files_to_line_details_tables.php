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
        Schema::table('quote_line_details', function (Blueprint $table) {
            $table->string('cam_file')->nullable()->after('cad_file');
            $table->string('cad_file_path')->nullable()->after('cam_file');
            $table->string('cam_file_path')->nullable()->after('cad_file_path');
        });

        Schema::table('order_line_details', function (Blueprint $table) {
            $table->string('cam_file')->nullable()->after('cad_file');
            $table->string('cad_file_path')->nullable()->after('cam_file');
            $table->string('cam_file_path')->nullable()->after('cad_file_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quote_line_details', function (Blueprint $table) {
            $table->dropColumn(['cam_file', 'cad_file_path', 'cam_file_path']);
        });

        Schema::table('order_line_details', function (Blueprint $table) {
            $table->dropColumn(['cam_file', 'cad_file_path', 'cam_file_path']);
        });
    }
};
