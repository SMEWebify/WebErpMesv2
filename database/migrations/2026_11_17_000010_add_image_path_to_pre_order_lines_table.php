<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Vignette de la pièce fournie par l'extracteur PDF (colonne image_path du CSV),
    // relative au dossier de sortie de l'extracteur. Ex. gtlaser/<pdf>/L01.png
    public function up(): void
    {
        Schema::table('pre_order_lines', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('total_price');
        });
    }

    public function down(): void
    {
        Schema::table('pre_order_lines', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};
