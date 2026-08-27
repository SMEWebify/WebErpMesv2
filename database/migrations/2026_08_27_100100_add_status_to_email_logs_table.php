<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Suivi de l'envoi : les mails restent en `pending` tant que le SMTP n'a pas
 * confirmé, passent en `sent` sur succès et en `failed` avec le message
 * d'erreur SMTP sinon. Le nom original du fichier attaché est mémorisé pour
 * pouvoir le proposer au téléchargement plus tard (le champ existant
 * `attachment` porte le hash de stockage, pas parlant côté écran).
 *
 * Migration additive : les lignes existantes restent lisibles ; on ne remet
 * pas leur statut à jour puisqu'on n'a aucune source de vérité rétroactive.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->string('status', 16)->default('pending')->after('attachment');
            $table->timestamp('sent_at')->nullable()->after('status');
            $table->text('error')->nullable()->after('sent_at');
            $table->string('attachment_original_name')->nullable()->after('error');
            $table->unsignedBigInteger('sent_by_user_id')->nullable()->after('attachment_original_name');

            $table->index('status');
            $table->index('sent_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['sent_by_user_id']);
            $table->dropColumn([
                'status',
                'sent_at',
                'error',
                'attachment_original_name',
                'sent_by_user_id',
            ]);
        });
    }
};
