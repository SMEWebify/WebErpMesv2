<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Référence d'une société chez un logiciel tiers (chiffrage, CRM) : l'appel suivant
 * du même logiciel retrouve la fiche par son identifiant à lui (API prospect).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_external_refs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('companies_id')->constrained('companies')->cascadeOnDelete();
            $table->string('system', 50);
            $table->string('external_id', 191);
            $table->timestamps();

            $table->unique(['system', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_external_refs');
    }
};
