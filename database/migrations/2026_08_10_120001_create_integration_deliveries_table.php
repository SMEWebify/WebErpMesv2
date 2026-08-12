<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Journal des livraisons entrantes/sortantes par endpoint d'intégration.
     *
     * Une ligne par tentative HTTP (in ou out). Sert à :
     *  - dédupliquer les events entrants via unique(direction, event_id)
     *  - afficher un log filtrable dans l'admin
     *  - permettre le rejeu manuel d'un event raté
     *
     * Purge automatique au-delà de la rétention configurée (~30j) via une
     * commande planifiée séparée.
     */
    public function up(): void
    {
        Schema::create('integration_deliveries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('integration_endpoint_id')
                ->constrained('integration_endpoints')
                ->cascadeOnDelete();

            $table->string('direction', 5);
            $table->uuid('event_id');
            $table->string('event_type', 100);

            $table->unsignedSmallInteger('http_status')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();

            $table->json('payload')->nullable();
            $table->text('response_body')->nullable();
            $table->text('error')->nullable();

            $table->dateTime('occurred_at');
            $table->dateTime('processed_at')->nullable();

            $table->timestamps();

            $table->unique(['direction', 'event_id']);
            $table->index(['integration_endpoint_id', 'created_at']);
            $table->index('event_type');
            $table->index('processed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_deliveries');
    }
};
