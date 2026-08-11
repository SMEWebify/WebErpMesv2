<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Trace le dernier event `job.status_changed` appliqué à un Order.
     * Sert d'ancre pour l'anti-régression : si un event arrive avec occurred_at
     * antérieur à cette valeur, on l'ignore (event tardif / hors-séquence).
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dateTime('n2p_last_status_event_at')->nullable()->after('n2p_last_push_error');
            // Index — lu à chaque event job.status_changed (HandleJobStatusEvent
            // compare $order->n2p_last_status_event_at >= $occurredAt pour
            // filtrer les events tardifs). Scan table complet sans index dès
            // que la table orders grossit.
            $table->index('n2p_last_status_event_at', 'orders_n2p_last_status_event_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_n2p_last_status_event_at_idx');
            $table->dropColumn('n2p_last_status_event_at');
        });
    }
};
