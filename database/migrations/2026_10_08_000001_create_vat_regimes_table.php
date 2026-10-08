<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Régime de TVA d'un tiers (axe « client/fournisseur » de la matrice de TVA).
 *
 * Table de référence ADMINISTRABLE : l'administrateur ajoute, retire ou renomme
 * un régime depuis l'écran Comptabilité → Matrice TVA, sans intervention
 * développeur. Seed fourni : FR, FRANCHISE, UE, EXPORT, AUTOLIQ (VatRegimeSeeder).
 *
 * Table nouvelle, aucune donnée existante touchée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vat_regimes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vat_regimes');
    }
};
