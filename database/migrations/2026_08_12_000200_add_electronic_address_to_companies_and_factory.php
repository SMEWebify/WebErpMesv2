<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adresse électronique de facturation (EN 16931 : BT-34 vendeur, BT-49 acheteur).
 *
 * C'est l'adresse d'acheminement sur le réseau Peppol, composée d'un Scheme ID
 * et d'un Participant ID — par exemple `0225:853322915` pour la France, où 0225
 * désigne l'annuaire français et le participant est l'adresse électronique de
 * facturation (le plus souvent le SIREN).
 *
 * Pourquoi une colonne dédiée plutôt que réutiliser `siren` : l'adresse ne se
 * déduit pas toujours du SIREN. Une entreprise peut ouvrir plusieurs lignes
 * d'annuaire (SIREN_SERVICEACHATS…) pour son organisation interne, et en bac à
 * sable l'adresse n'a aucun rapport avec le SIREN. Faute de cette colonne, une
 * facture destinée à une adresse spécifique serait routée au mauvais endroit,
 * ou refusée si l'adresse déduite n'existe pas dans l'annuaire.
 *
 * Laissée vide, le SIREN sert de valeur par défaut : c'est le choix que fera la
 * majorité des entreprises françaises.
 */
return new class extends Migration
{
    /** Annuaire de la facturation électronique française. */
    private const DEFAULT_SCHEME = '0225';

    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('electronic_address')->nullable()->after('intra_community_vat');
            $table->string('electronic_address_scheme', 4)->default(self::DEFAULT_SCHEME)->after('electronic_address');
        });

        Schema::table('factory', function (Blueprint $table) {
            $table->string('electronic_address')->nullable()->after('vat_num');
            $table->string('electronic_address_scheme', 4)->default(self::DEFAULT_SCHEME)->after('electronic_address');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['electronic_address', 'electronic_address_scheme']);
        });

        Schema::table('factory', function (Blueprint $table) {
            $table->dropColumn(['electronic_address', 'electronic_address_scheme']);
        });
    }
};
