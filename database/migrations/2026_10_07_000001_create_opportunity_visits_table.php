<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Visite sur site saisie au téléphone : relevés de cotes, notes, transcription
 * du mémo vocal et compte rendu. Les photos vivent dans la GED de l'opportunité
 * (hashtag visite-{id}) ; l'audio n'est jamais stocké.
 *
 * Table nouvelle, aucune donnée existante touchée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunity_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opportunities_id')->constrained('opportunities')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            // Événement « Visite sur site » créé à la validation du compte rendu.
            $table->foreignId('opportunities_events_logs_id')->nullable()
                ->constrained('opportunities_events_logs')->nullOnDelete();
            $table->dateTime('visited_at');
            $table->text('notes')->nullable();
            // [{label, value, unit, note}]
            $table->json('measurements')->nullable();
            $table->longText('transcript')->nullable();
            $table->longText('report')->nullable();
            // 1 = brouillon, 2 = validée
            $table->unsignedTinyInteger('statu')->default(1);
            $table->timestamps();

            $table->index(['opportunities_id', 'statu']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunity_visits');
    }
};
