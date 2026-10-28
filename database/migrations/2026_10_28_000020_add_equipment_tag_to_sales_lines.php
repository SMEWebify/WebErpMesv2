<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Repères d'équipement (tags) des lignes de devis, de commande et d'ARC.
 *
 * Purement additive : colonne nullable, une ligne existante n'a pas de repère
 * et s'imprime à l'identique. Liste JSON de chaînes (App\Casts\EquipmentTags) :
 * une ligne de quantité > 1 peut porter un repère par équipement. Colonne
 * texte et non JSON pour que la recherche LIKE suive la collation de la table
 * (insensible à la casse sous MySQL).
 */
return new class extends Migration
{
    private const TABLES = ['quote_lines', 'order_lines', 'order_confirmation_lines'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->text('equipment_tag')->nullable()->after('label');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn('equipment_tag');
            });
        }
    }
};
