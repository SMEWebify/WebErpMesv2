<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Colonne JSON metadata par endpoint — porte les toggles/valeurs métier
     * qui étaient auparavant dans la table settings globale (n2p_send_tasks,
     * n2p_send_on_order_status_from/to, n2p_job_status_on_send, n2p_priority_default).
     *
     * Voir IntegrationEndpoint::META_DEFAULTS et ::meta() pour la lecture.
     * Rend chaque endpoint autoportant côté transport ET métier — utile quand
     * plusieurs endpoints doivent coexister avec des toggles différents.
     */
    public function up(): void
    {
        Schema::table('integration_endpoints', function (Blueprint $table) {
            $table->json('metadata')->nullable()->after('events');
        });
    }

    public function down(): void
    {
        Schema::table('integration_endpoints', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });
    }
};
