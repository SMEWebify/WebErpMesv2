<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Configurateurs externes (docs/conception/configurateurs-externes.md) :
 * - external_configurators : liaisons réglées dans Intégrations → Configurateurs ;
 * - configurator_sessions : sessions ouvertes par l'ERP, seules à pouvoir accepter un résultat ;
 * - line_configuration_items : options, cotes, exigences et référence fournisseur d'une
 *   ligne, en lignes structurées (polymorphe : ligne de devis, de commande).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_configurators', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('label');
            $table->string('base_url');
            $table->text('api_key')->nullable();
            $table->string('display', 10)->default('iframe');
            $table->json('document_types')->nullable();
            $table->json('families')->nullable();
            $table->string('price_mode', 10)->default('gross');
            $table->boolean('active')->default(false);
            $table->timestamp('last_check_at')->nullable();
            $table->string('last_error', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('configurator_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('external_configurator_id')->constrained('external_configurators')->cascadeOnDelete();
            $table->string('remote_session_id')->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('document_type', 10);
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('line_id')->nullable();
            $table->string('reference_externe');
            $table->string('status', 12)->default('pending');
            $table->string('result_id')->nullable()->unique();
            $table->json('result')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->index(['document_type', 'document_id']);
        });

        Schema::create('line_configuration_items', function (Blueprint $table) {
            $table->id();
            $table->morphs('configurable');
            $table->string('kind', 12);
            $table->string('code', 100)->nullable();
            $table->string('label');
            $table->string('value')->nullable();
            $table->string('unit', 20)->nullable();
            $table->decimal('unit_price', 12, 3)->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('line_configuration_items');
        Schema::dropIfExists('configurator_sessions');
        Schema::dropIfExists('external_configurators');
    }
};
