<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Offres fournisseurs indicées (App\Services\Purchases\PurchaseOfferRevisions).
 *
 * - Demande de prix : référence, indice, date et validité de l'offre reçue ;
 *   lien au référent (famille à plat, le référent garde null) et date de
 *   remplacement par l'indice suivant. « Remplacé » n'est pas un statut : le
 *   statut 1-6 est réécrit par la création de commande et par les flèches.
 * - Commande d'achat : référence et indice de l'offre dont elle sort.
 *
 * Colonnes nullable, sans clé étrangère (comme rfq_group_id) : l'existant n'a
 * ni offre ni indice et s'imprime à l'identique.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases_quotations', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_purchase_quotation_id')->nullable()->index();
            $table->string('supplier_offer_reference')->nullable();
            $table->string('supplier_offer_revision', 20)->nullable();
            $table->date('supplier_offer_date')->nullable();
            $table->date('supplier_offer_validity')->nullable();
            $table->timestamp('superseded_at')->nullable();
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->string('supplier_offer_reference')->nullable();
            $table->string('supplier_offer_revision', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['supplier_offer_reference', 'supplier_offer_revision']);
        });

        Schema::table('purchases_quotations', function (Blueprint $table) {
            $table->dropIndex(['parent_purchase_quotation_id']);
            $table->dropColumn([
                'parent_purchase_quotation_id',
                'supplier_offer_reference',
                'supplier_offer_revision',
                'supplier_offer_date',
                'supplier_offer_validity',
                'superseded_at',
            ]);
        });
    }
};
