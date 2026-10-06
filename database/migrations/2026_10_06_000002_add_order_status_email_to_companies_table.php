<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Opt-in par client : prévenir le contact de la commande à chaque
     * changement d'étape. Faux par défaut, aucun client existant ne se met
     * à recevoir des mails après déploiement.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('order_status_email')->default(false)->after('quoted_delivery_note');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('order_status_email');
        });
    }
};
