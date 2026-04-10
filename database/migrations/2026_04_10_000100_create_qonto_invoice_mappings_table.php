<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qonto_invoice_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('invoice_id');
            $table->string('qonto_invoice_id')->nullable();      // ID côté Qonto après dépôt
            $table->string('lifecycle_status')->default('pending'); // Voir QontoInvoiceSyncService::LIFECYCLE_*
            $table->string('rejection_reason')->nullable();          // Motif si rejeté/refusé
            $table->timestamp('submitted_at')->nullable();           // Date de dépôt vers Qonto
            $table->timestamp('acknowledged_at')->nullable();        // Date d'accusé réception Qonto
            $table->timestamps();

            $table->unique(['tenant_id', 'invoice_id']);
            $table->index(['tenant_id', 'lifecycle_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qonto_invoice_mappings');
    }
};
