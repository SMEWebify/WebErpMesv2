<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Responsable d'une activité / d'un événement d'opportunité, pour le calendrier commercial.
 * Additive et nullable : les lignes existantes restent sans responsable et le calendrier
 * retombe alors sur le commercial de l'opportunité.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opportunities_activities_logs', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('opportunities_id')
                ->constrained('users')->nullOnDelete();
        });

        Schema::table('opportunities_events_logs', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('opportunities_id')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('opportunities_activities_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('opportunities_events_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
