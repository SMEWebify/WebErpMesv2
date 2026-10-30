<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Accusé de réception (AR) du fournisseur, ligne par ligne
 * (App\Services\Purchases\SupplierAcknowledgement) :
 * - purchase_lines.supplier_ack_at : date de l'AR reçu ;
 * - purchase_lines.supplier_ack_reference : référence de l'AR chez le fournisseur.
 * La date confirmée existe déjà (`confirmed_delivery_date`, 2026_10_30_000030) ; le
 * document de l'AR va dans la GED de la commande (rôle `ar_fournisseur`).
 *
 * purchases.ordered_at : passage de « en cours » à commandée, posé par Purchases::booted,
 * point de départ de « sans AR depuis N jours ». Nul pour l'existant : la liste retombe
 * alors sur la date de création.
 *
 * Colonnes nullable : rien ne change pour l'existant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_lines', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_lines', 'supplier_ack_at')) {
                $table->date('supplier_ack_at')->nullable()->after('confirmed_delivery_date');
            }
            if (! Schema::hasColumn('purchase_lines', 'supplier_ack_reference')) {
                $table->string('supplier_ack_reference', 100)->nullable()->after('supplier_ack_at');
            }
        });

        if (! Schema::hasColumn('purchases', 'ordered_at')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->timestamp('ordered_at')->nullable()->after('statu');
            });
        }
    }

    public function down(): void
    {
        Schema::table('purchase_lines', function (Blueprint $table) {
            foreach (['supplier_ack_reference', 'supplier_ack_at'] as $column) {
                if (Schema::hasColumn('purchase_lines', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if (Schema::hasColumn('purchases', 'ordered_at')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->dropColumn('ordered_at');
            });
        }
    }
};
