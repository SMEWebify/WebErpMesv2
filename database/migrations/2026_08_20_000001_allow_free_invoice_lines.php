<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lignes de facture libres.
 *
 * Jusqu'ici une ligne de facture ne pouvait exister qu'en face d'une ligne de
 * commande : `order_line_id` était NOT NULL et le libellé, le code, l'unité et
 * la TVA étaient tous lus depuis `order_lines`. Impossible donc de facturer un
 * frais de port, un frais de dossier ou une prestation ponctuelle oubliés au
 * moment de la commande.
 *
 * On rend `order_line_id` nullable et on porte sur la ligne de facture les
 * champs descriptifs, dans la continuité du snapshot de prix introduit par
 * 2026_04_21_000003_add_price_snapshot_to_invoice_lines.
 *
 * Même traitement sur `credit_note_lines` : un avoir doit pouvoir porter sur
 * une ligne libre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->integer('order_line_id')->nullable()->change();

            $table->string('label')->nullable()->after('delivery_line_id');
            $table->string('code')->nullable()->after('label');
            $table->unsignedBigInteger('product_id')->nullable()->after('code');
            $table->unsignedBigInteger('methods_units_id')->nullable()->after('product_id');
            $table->unsignedBigInteger('accounting_vats_id')->nullable()->after('vat_rate');
        });

        // La FK doit tomber avant de pouvoir modifier la colonne sous MySQL.
        Schema::table('credit_note_lines', function (Blueprint $table) {
            $table->dropForeign(['order_line_id']);
        });

        Schema::table('credit_note_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('order_line_id')->nullable()->change();

            // Snapshot descriptif, nécessaire dès lors que la ligne d'origine
            // n'est plus systématiquement une ligne de commande.
            $table->string('label')->nullable()->after('invoice_line_id');
            $table->decimal('discount', 5, 2)->nullable()->after('unit_price');
            $table->decimal('vat_rate', 5, 2)->nullable()->after('discount');
            $table->unsignedBigInteger('accounting_vats_id')->nullable()->after('vat_rate');
        });

        Schema::table('credit_note_lines', function (Blueprint $table) {
            $table->foreign('order_line_id')->references('id')->on('order_lines')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('credit_note_lines', function (Blueprint $table) {
            $table->dropForeign(['order_line_id']);
        });

        Schema::table('credit_note_lines', function (Blueprint $table) {
            $table->dropColumn(['label', 'discount', 'vat_rate', 'accounting_vats_id']);
            $table->unsignedBigInteger('order_line_id')->nullable(false)->change();
        });

        Schema::table('credit_note_lines', function (Blueprint $table) {
            $table->foreign('order_line_id')->references('id')->on('order_lines')->onDelete('cascade');
        });

        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->dropColumn(['label', 'code', 'product_id', 'methods_units_id', 'accounting_vats_id']);
            $table->integer('order_line_id')->nullable(false)->change();
        });
    }
};
