<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Migre les 6 settings vivants n2p_* de la table `settings` vers
     * `integration_endpoints.metadata` (endpoint n2p+outbound).
     *
     * Correspondance :
     *   settings.n2p_enabled                     → integration_endpoints.is_active (déjà géré, ici on force à true si le setting l'était)
     *   settings.n2p_send_on_order_status_from   → metadata.status_transition_from
     *   settings.n2p_send_on_order_status_to     → metadata.status_transition_to
     *   settings.n2p_send_tasks                  → metadata.send_tasks
     *   settings.n2p_job_status_on_send          → metadata.job_status_on_send
     *   settings.n2p_priority_default            → metadata.default_priority
     *
     * Après migration, les settings originaux sont supprimés (plus lus par le
     * code). Idempotente : re-jouer ne casse rien.
     */
    public function up(): void
    {
        $endpoint = DB::table('integration_endpoints')
            ->where('system_code', 'n2p')
            ->where('direction', 'outbound')
            ->first();

        if (! $endpoint) {
            // Endpoint pas encore seedé (env vierge) — rien à migrer, la config
            // sera saisie via l'UI directement dans metadata.
            return;
        }

        $settings = DB::table('settings')
            ->whereIn('key', [
                'n2p_enabled',
                'n2p_send_on_order_status_from',
                'n2p_send_on_order_status_to',
                'n2p_send_tasks',
                'n2p_job_status_on_send',
                'n2p_priority_default',
            ])
            ->pluck('value', 'key')
            ->all();

        $currentMeta = $endpoint->metadata ? json_decode($endpoint->metadata, true) : [];
        if (! is_array($currentMeta)) {
            $currentMeta = [];
        }

        // On ne surcharge PAS ce qui est déjà en metadata (permet de re-jouer
        // sans écraser un réglage saisi manuellement après la première pass).
        $merged = $currentMeta + [
            'status_transition_from' => $this->cleanString($settings['n2p_send_on_order_status_from'] ?? null, 'OPEN'),
            'status_transition_to'   => $this->cleanString($settings['n2p_send_on_order_status_to'] ?? null, 'IN_PROGRESS'),
            'send_tasks'             => $this->cleanBool($settings['n2p_send_tasks'] ?? null, true),
            'job_status_on_send'     => $this->cleanString($settings['n2p_job_status_on_send'] ?? null, 'released'),
            'default_priority'       => $this->cleanInt($settings['n2p_priority_default'] ?? null, 3),
        ];

        // n2p_enabled est un master switch — on l'aligne sur is_active.
        // Si l'un des deux dit "on", on considère l'endpoint actif.
        $enabled = $this->cleanBool($settings['n2p_enabled'] ?? null, false);
        $newIsActive = $enabled || (bool) $endpoint->is_active;

        DB::table('integration_endpoints')
            ->where('id', $endpoint->id)
            ->update([
                'metadata'   => json_encode($merged),
                'is_active'  => $newIsActive,
                'updated_at' => now(),
            ]);

        // Purge les settings migrés — plus lus par le code après ce commit.
        DB::table('settings')
            ->whereIn('key', [
                'n2p_enabled',
                'n2p_send_on_order_status_from',
                'n2p_send_on_order_status_to',
                'n2p_send_tasks',
                'n2p_job_status_on_send',
                'n2p_priority_default',
                // 3 morts historiques (URL/token/verify_ssl étaient déjà remplacés
                // par les colonnes dédiées de integration_endpoints) — on les
                // supprime au passage pour ne pas laisser de résidus.
                'n2p_base_url',
                'n2p_api_token',
                'n2p_verify_ssl',
            ])
            ->delete();
    }

    /**
     * Non-réversible en pratique : les settings sont supprimés, on ne peut pas
     * les recréer avec certitude (la source de vérité est maintenant metadata).
     * down() est un no-op — un rollback devrait passer par une bascule manuelle.
     */
    public function down(): void
    {
        // Volontairement vide — cf. commentaire ci-dessus.
    }

    private function cleanString(mixed $value, string $default): string
    {
        if ($value === null || $value === '') {
            return $default;
        }
        return (string) $value;
    }

    private function cleanBool(mixed $value, bool $default): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function cleanInt(mixed $value, int $default): int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return $default;
        }
        return (int) $value;
    }
};
