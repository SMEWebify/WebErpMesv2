<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Souscriptions d'intégration entrantes/sortantes avec système externe.
     *
     * Une paire (system_code, direction) = un endpoint. Ex : ('n2p', 'outbound')
     * pour ce que l'ERP envoie à Nest2Prod, ('n2p', 'inbound') pour ce que
     * Nest2Prod envoie à l'ERP.
     *
     * Les secrets (bearer_token, hmac_secret) sont chiffrés au niveau du modèle
     * via le cast 'encrypted', pas au niveau du seed/migration.
     */
    public function up(): void
    {
        Schema::create('integration_endpoints', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('system_code', 50);
            $table->string('direction', 20);

            $table->string('url', 500)->nullable();

            $table->string('auth_method', 20)->default('hmac');
            $table->text('bearer_token')->nullable();
            $table->text('hmac_secret')->nullable();
            $table->string('hmac_header', 100)->default('X-Signature-256');
            $table->string('timestamp_header', 100)->default('X-Timestamp');
            $table->unsignedInteger('timestamp_tolerance_s')->default(300);
            $table->boolean('verify_ssl')->default(true);

            $table->json('events')->nullable();

            $table->boolean('is_active')->default(false);

            $table->unsignedTinyInteger('retry_max')->default(5);
            $table->json('retry_backoff')->nullable();

            $table->dateTime('last_success_at')->nullable();
            $table->dateTime('last_error_at')->nullable();
            $table->text('last_error_message')->nullable();

            $table->timestamps();

            $table->unique(['system_code', 'direction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_endpoints');
    }
};
