<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Avis de livraison du fournisseur : transporteur, n° de suivi et date d'expédition
 * repris de son mail ou de son BL, à côté du n° de BL déjà saisi. Nullable : les
 * réceptions existantes n'en ont pas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_receipts', function (Blueprint $table) {
            $table->string('carrier', 100)->nullable()->after('delivery_note_number');
            $table->string('tracking_number', 100)->nullable()->after('carrier');
            $table->date('shipped_at')->nullable()->after('tracking_number');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_receipts', function (Blueprint $table) {
            $table->dropColumn(['carrier', 'tracking_number', 'shipped_at']);
        });
    }
};
