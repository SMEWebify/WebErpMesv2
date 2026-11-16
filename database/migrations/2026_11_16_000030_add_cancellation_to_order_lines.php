<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Annulation d'une ligne de commande (reste non livré annulé par le client).
 * Colonnes additives et nullables : aucune ligne existante n'est annulée.
 * Écrites seulement par App\Services\Workflow\OrderLineCancellation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_lines', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->string('cancel_reason', 255)->nullable();
            // Quantité annulée (la quantité commandée tombe à la quantité livrée)
            $table->decimal('cancelled_qty', 10, 3)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('order_lines', function (Blueprint $table) {
            $table->dropColumn(['cancelled_at', 'cancelled_by', 'cancel_reason', 'cancelled_qty']);
        });
    }
};
