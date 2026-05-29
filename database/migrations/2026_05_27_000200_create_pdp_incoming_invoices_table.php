<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Boîte de réception des factures fournisseurs entrantes (facturation
 * électronique). Stocke le document reçu et son traitement avant conversion
 * en facture d'achat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdp_incoming_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('manual');        // source : 'qonto', 'manual'…
            $table->string('external_id')->nullable();             // id côté PDP si reçu via plateforme
            $table->unsignedBigInteger('supplier_company_id')->nullable(); // fournisseur WEM rapproché
            $table->string('seller_name')->nullable();
            $table->string('seller_vat')->nullable();              // TVA intracom du vendeur
            $table->string('seller_legal_id')->nullable();         // SIREN/SIRET du vendeur
            $table->string('invoice_number')->nullable();          // n° de facture fournisseur
            $table->date('issue_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('currency', 3)->nullable();
            $table->decimal('total_ht', 15, 2)->nullable();
            $table->decimal('total_vat', 15, 2)->nullable();
            $table->decimal('total_ttc', 15, 2)->nullable();
            $table->string('buyer_reference')->nullable();
            $table->string('status')->default('received');
            $table->unsignedBigInteger('purchase_invoice_id')->nullable(); // facture d'achat générée
            $table->longText('payload')->nullable();               // données Factur-X parsées (JSON)
            $table->timestamps();

            $table->index('provider');
            $table->index('status');
            $table->index('supplier_company_id');
            // Dédoublonnage : un même n° de facture par vendeur n'est ingéré qu'une fois.
            $table->unique(['seller_vat', 'invoice_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdp_incoming_invoices');
    }
};
