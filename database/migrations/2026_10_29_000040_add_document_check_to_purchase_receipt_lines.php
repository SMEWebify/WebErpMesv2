<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contrôle documentaire d'une ligne de réception par la qualité, distinct du contrôle
 * physique (inspected_by, accepted_qty…) : certificat reçu, comparé au type exigé sur la
 * ligne d'achat, puis visa. Colonnes nullables : une ligne existante n'est concernée que
 * si un certificat est exigé ou reçu (aucune avant cette version).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_receipt_lines', function (Blueprint $table) {
            // Certificat EN 10204 effectivement reçu avec la marchandise
            $table->string('received_certificate_type', 10)->nullable()->after('quality_non_conformity_id');
            $table->unsignedBigInteger('document_check_by')->nullable()->after('received_certificate_type');
            $table->timestamp('document_check_at')->nullable()->after('document_check_by');
            // pending | compliant | non_compliant ; null = pas encore visé
            $table->string('document_check_result', 20)->nullable()->after('document_check_at');
            $table->text('document_check_comment')->nullable()->after('document_check_result');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_receipt_lines', function (Blueprint $table) {
            $table->dropColumn([
                'received_certificate_type', 'document_check_by', 'document_check_at',
                'document_check_result', 'document_check_comment',
            ]);
        });
    }
};
