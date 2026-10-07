<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lignes de présentation sur les devis, commandes et ARC.
 *
 * Purement additive : les valeurs par défaut décrivent la ligne article
 * d'aujourd'hui, un document existant se calcule et s'imprime à l'identique.
 * - line_type   : article | section | subtotal | text (App\Enums\SalesLineType)
 * - hide_on_pdf : article compté dans le total mais non imprimé
 * - pdf_package : section imprimée au forfait (0 détail, 1 montant seul, 2 « 1 × unité »)
 */
return new class extends Migration
{
    private const TABLES = ['quote_lines', 'order_lines', 'order_confirmation_lines'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('line_type', 16)->default('article')->after('ordre');
                $t->boolean('hide_on_pdf')->default(false)->after('line_type');
                $t->unsignedTinyInteger('pdf_package')->default(0)->after('hide_on_pdf');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn(['line_type', 'hide_on_pdf', 'pdf_package']);
            });
        }
    }
};
