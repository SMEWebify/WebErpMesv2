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
        Schema::create('osh_conformites', function (Blueprint $table) {
            $table->id();
            $table->string('document_type');
            $table->text('description')->nullable();
            $table->date('expiration_date')->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer(column: 'statut')->default(1);
            // 1 = en cours, 2 = approuvé, 3 = expiré
            $table->foreignId('section_id')->constrained('methods_sections')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('osh_conformites');
    }
};
