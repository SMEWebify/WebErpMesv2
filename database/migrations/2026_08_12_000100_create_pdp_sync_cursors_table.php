<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Curseurs de synchronisation PDP.
 *
 * Les plateformes qui n'émettent pas de webhooks (c'est le cas de SUPER PDP) se
 * synchronisent par interrogation périodique avec un curseur : on ne redemande
 * que les objets dont l'id est strictement supérieur au dernier traité. Les id
 * étant garantis atomiquement strictement croissants côté plateforme, cela
 * assure de ne jamais « sauter » une facture — ce qu'un filtre par date de
 * création ne garantit pas.
 *
 * Une ligne par (plateforme, flux, tenant).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdp_sync_cursors', function (Blueprint $table) {
            $table->id();
            $table->string('provider');                          // 'superpdp'…
            $table->string('stream');                            // 'invoices_in', 'invoice_events'
            // 0 = pas de cloisonnement par tenant (installation mono-société).
            $table->unsignedBigInteger('tenant_id')->default(0);
            $table->unsignedBigInteger('last_id')->default(0);   // dernier id traité
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            // tenant_id non nullable : en MySQL deux NULL sont distincts, un
            // index unique nullable n'empêcherait pas les doublons de curseur.
            $table->unique(['provider', 'stream', 'tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdp_sync_cursors');
    }
};
