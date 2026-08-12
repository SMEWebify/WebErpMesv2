<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Index de `orders.n2p_last_status_event_at`.
     *
     * La colonne a été ajoutée par 2026_08_10_160000 sans index. Un index est
     * nécessaire parce que HandleJobStatusEvent compare cette colonne à chaque
     * event N2P entrant (`$order->n2p_last_status_event_at >= $occurredAt`)
     * pour filtrer les events tardifs. Sans index, scan complet de la table
     * orders à chaque event.
     *
     * Migration idempotente : si un env fraîchement provisionné après le fix
     * a déjà l'index (parce que 160000 a été rejouée avec l'index inline),
     * on skip proprement.
     */
    private const INDEX_NAME = 'orders_n2p_last_status_event_at_idx';

    public function up(): void
    {
        if ($this->indexExists('orders', self::INDEX_NAME)) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->index('n2p_last_status_event_at', self::INDEX_NAME);
        });
    }

    public function down(): void
    {
        if (! $this->indexExists('orders', self::INDEX_NAME)) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(self::INDEX_NAME);
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            $rows = DB::select('SHOW INDEX FROM ' . $table . ' WHERE Key_name = ?', [$indexName]);
            return count($rows) > 0;
        }

        // Fallback SQLite (tests) — Doctrine indirection déprécié en L12,
        // on passe par pragma.
        if ($driver === 'sqlite') {
            $rows = DB::select("PRAGMA index_list({$table})");
            foreach ($rows as $row) {
                if (($row->name ?? null) === $indexName) {
                    return true;
                }
            }
            return false;
        }

        return false;
    }
};
