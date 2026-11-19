<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marque d'une ligne issue d'un configurateur externe : le configurateur, la référence
 * de la configuration (pour la rouvrir) et la date. Nullable : les lignes existantes
 * et les lignes saisies à la main n'en ont pas.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['quote_lines', 'order_lines'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->unsignedBigInteger('external_configurator_id')->nullable();
                $t->string('configuration_ref')->nullable();
                $t->timestamp('configured_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['quote_lines', 'order_lines'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn(['external_configurator_id', 'configuration_ref', 'configured_at']);
            });
        }
    }
};
