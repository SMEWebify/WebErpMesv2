<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Conditions et mode de règlement propres au client (ex. virement à 60 jours net),
 * proposés à la création de ses devis et commandes à la place des valeurs par
 * défaut de l'instance. Nullable : sans valeur, rien ne change.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->unsignedBigInteger('accounting_payment_conditions_id')->nullable()->after('discount');
            $table->unsignedBigInteger('accounting_payment_methods_id')->nullable()->after('accounting_payment_conditions_id');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['accounting_payment_conditions_id', 'accounting_payment_methods_id']);
        });
    }
};
