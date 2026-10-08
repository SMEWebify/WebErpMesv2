<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Signature électronique des devis publics.
 *
 * - `esignature_settings` : table singleton, comme `mail_settings`. Les identifiants
 *   du prestataire se saisissent dans l'écran Intégrations, pas dans le .env ; la clé
 *   privée RSA et la clé HMAC sont chiffrées par le cast `encrypted` du modèle.
 * - `quote_signatures` : une ligne par enveloppe envoyée. Un devis peut en avoir
 *   plusieurs (enveloppe annulée parce que le devis a changé, refus puis renvoi) ;
 *   au plus une est en attente à un instant donné.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esignature_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 32)->default('docusign');
            // 'demo' (bac à sable) ou 'production' : décide du serveur OAuth.
            $table->string('environment', 16)->default('demo');
            $table->string('integration_key')->nullable();
            // GUID de l'utilisateur DocuSign au nom duquel les enveloppes partent.
            $table->string('api_user_id')->nullable();
            $table->string('account_id')->nullable();
            // Cipher gonfle la taille en base64 → text plutôt que string.
            $table->text('private_key')->nullable();
            $table->text('hmac_key')->nullable();
            // 'embedded' : le client signe depuis la page publique ;
            // 'email' : DocuSign envoie la demande à l'adresse du contact.
            $table->string('signing_mode', 16)->default('embedded');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('quote_signatures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quotes_id');
            $table->string('provider', 32);
            $table->string('envelope_id')->unique();
            $table->string('signing_mode', 16);
            // Identifiant du signataire intégré (clientUserId DocuSign), null en mode e-mail.
            $table->string('client_user_id')->nullable();
            // sent | delivered | completed | declined | voided
            $table->string('status', 16)->index();
            $table->string('signer_name');
            $table->string('signer_email');
            // Total TTC au moment de l'envoi : un devis modifié depuis invalide l'enveloppe.
            $table->decimal('quote_total', 15, 2);
            $table->text('status_reason')->nullable();
            $table->unsignedBigInteger('signed_file_id')->nullable();
            $table->unsignedBigInteger('certificate_file_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            $table->foreign('quotes_id')->references('id')->on('quotes')->cascadeOnDelete();
            $table->index(['quotes_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_signatures');
        Schema::dropIfExists('esignature_settings');
    }
};
