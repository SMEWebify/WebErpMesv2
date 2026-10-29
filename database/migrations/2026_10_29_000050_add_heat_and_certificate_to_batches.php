<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Coulée et certificat matière d'un lot. Colonnes nullable : les lots existants
 * restent valides sans rien renseigner.
 *
 * - heat_number              : n° de coulée de l'aciérie (indexé, c'est la clé de recherche) ;
 * - supplier_batch_code      : n° de lot du fournisseur, s'il diffère de la coulée ;
 * - certificate_type         : certificat EN 10204 reçu (config purchase_quality.certificate_types) ;
 * - companies_id             : fournisseur ;
 * - purchase_receipt_line_id : ligne de réception d'origine (point d'entrée de la traçabilité montante).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->string('heat_number')->nullable()->after('code')->index();
            $table->string('supplier_batch_code')->nullable()->after('heat_number');
            $table->string('certificate_type', 10)->nullable()->after('supplier_batch_code');
            $table->foreignId('companies_id')->nullable()->after('product_id')
                ->constrained('companies')->nullOnDelete();
            $table->foreignId('purchase_receipt_line_id')->nullable()->after('companies_id')
                ->constrained('purchase_receipt_lines')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('purchase_receipt_line_id');
            $table->dropConstrainedForeignId('companies_id');
            $table->dropIndex(['heat_number']);
            $table->dropColumn(['heat_number', 'supplier_batch_code', 'certificate_type']);
        });
    }
};
