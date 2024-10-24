<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('accounting_entries', function (Blueprint $table) {
            $table->id();  // Identifiant unique pour chaque écriture
            $table->string('journal_code', 10);  // Code journal de l'écriture comptable
            $table->string('journal_label', 255);  // Libellé journal de l'écriture comptable
            $table->integer('sequence_number');  // Numéro de séquence
            $table->date('accounting_date');  // Date de comptabilisation
            $table->string('account_number', 20);  // Numéro de compte
            $table->string('account_label', 255);  // Libellé de compte
            $table->string('justification_reference', 255); 
            $table->string('justification_date', 255);  
            $table->string('auxiliary_account_number', 20)->nullable();  // Numéro de compte auxiliaire
            $table->string('auxiliary_account_label', 255)->nullable();  // Libellé de compte auxiliaire
            $table->string('document_reference', 100)->nullable();  // Référence de la pièce justificative
            $table->date('document_date')->nullable();  // Date de la pièce justificative
            $table->string('entry_label', 255);  // Libellé de l'écriture comptable
            $table->decimal('debit_amount', 15, 2)->nullable();  // Montant au débit
            $table->decimal('credit_amount', 15, 2)->nullable();  // Montant au crédit
            $table->string('entry_lettering', 20)->nullable();  // Lettrage de l'écriture comptable
            $table->date('lettering_date')->nullable();  // Date de lettrage
            $table->date('validation_date');  // Date de validation de l'écriture comptable
            $table->string('currency_code', 15)->nullable();  // Montant en devise
            $table->unsignedBigInteger('invoice_line_id', 3)->nullable();  
            $table->unsignedBigInteger('purchase_invoice_line_id', 3)->nullable(); 
            $table->timestamps(); // Created at and updated at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_entries');
    }
};
