<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Date de livraison confirmée par le fournisseur (accusé de réception de commande).
 * Prime sur la date demandée (`delivery_date`) pour le taux de livraison à l'heure
 * (App\Services\Purchases\SupplierOnTimeDelivery). Nullable : rien ne change pour l'existant.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('purchase_lines', 'confirmed_delivery_date')) {
            return;
        }

        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->date('confirmed_delivery_date')->nullable()->after('delivery_date');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('purchase_lines', 'confirmed_delivery_date')) {
            Schema::table('purchase_lines', function (Blueprint $table) {
                $table->dropColumn('confirmed_delivery_date');
            });
        }
    }
};
