<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nature de TVA d'une ligne (axe « article/ligne » de la matrice de TVA).
 *
 * C'est aussi le LEVIER D'IMPUTATION : ajouter une nature (ex. « outillage »)
 * crée des lignes de matrice avec leur propre compte de vente (707 outillage vs
 * 701 usinage), sans toucher au code. Table de référence ADMINISTRABLE.
 * Seed fourni : fabrication, prestation, port, acompte, matiere, marchandise,
 * sous_traitance, frais, immobilisation (VatNatureSeeder).
 *
 * Table nouvelle, aucune donnée existante touchée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vat_natures', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vat_natures');
    }
};
