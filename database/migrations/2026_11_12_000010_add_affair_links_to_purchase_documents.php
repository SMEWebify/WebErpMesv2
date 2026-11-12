<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Achats rattachés à une affaire sans passer par une tâche.
 *
 * - En-tête des commandes d'achat et des demandes de prix : opportunité, devis
 *   ou commande client (un seul des trois, choisi à la main ; les achats
 *   génériques — frais, outillage dédié — n'ont pas de tâche).
 * - purchase_quotation_lines.retained_at : réponse fournisseur retenue pour le
 *   coût de la tâche d'un devis (une seule par tâche).
 *
 * Colonnes nullable, sans clé étrangère (comme rfq_group_id) : l'existant reste
 * non rattaché.
 */
return new class extends Migration
{
    private const TABLES = ['purchases', 'purchases_quotations'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->unsignedBigInteger('opportunities_id')->nullable()->index();
                $blueprint->unsignedBigInteger('quotes_id')->nullable()->index();
                $blueprint->unsignedBigInteger('orders_id')->nullable()->index();
            });
        }

        Schema::table('purchase_quotation_lines', function (Blueprint $table) {
            $table->timestamp('retained_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_quotation_lines', function (Blueprint $table) {
            $table->dropColumn('retained_at');
        });

        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropIndex(['opportunities_id']);
                $blueprint->dropIndex(['quotes_id']);
                $blueprint->dropIndex(['orders_id']);
                $blueprint->dropColumn(['opportunities_id', 'quotes_id', 'orders_id']);
            });
        }
    }
};
