<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Demandes entrantes : une piste créée depuis un e-mail (dépôt d'un .eml / .msg ou
 * appel de l'API par n8n) garde l'objet, l'heure d'arrivée, l'expéditeur et le
 * Message-ID, qui empêche de créer deux pistes pour le même e-mail. Nullable : les
 * pistes saisies à la main n'en ont pas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('subject')->nullable()->after('companies_addresses_id');
            $table->dateTime('received_at')->nullable()->after('subject');
            $table->string('sender_email')->nullable()->after('received_at');
            $table->string('message_id')->nullable()->unique()->after('sender_email');
            $table->index('received_at');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropUnique(['message_id']);
            $table->dropIndex(['received_at']);
            $table->dropColumn(['subject', 'received_at', 'sender_email', 'message_id']);
        });
    }
};
