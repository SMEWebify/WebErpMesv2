<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pre_order_pdf_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('original_name');
            $table->string('stored_name');
            // sha256 du contenu binaire du PDF, calculé avant renommage → détecte le même
            // document physique quel que soit son nom de fichier.
            $table->string('checksum', 64)->unique();
            $table->timestamp('uploaded_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pre_order_pdf_uploads');
    }
};
