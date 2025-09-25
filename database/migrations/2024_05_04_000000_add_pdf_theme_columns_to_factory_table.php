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
        Schema::table('factory', function (Blueprint $table) {
            $table->string('pdf_theme')->default('default')->after('pdf_header_font_color');
            $table->text('pdf_custom_css')->nullable()->after('pdf_theme');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('factory', function (Blueprint $table) {
            $table->dropColumn(['pdf_theme', 'pdf_custom_css']);
        });
    }
};
