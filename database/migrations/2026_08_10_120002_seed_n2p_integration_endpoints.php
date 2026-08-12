<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    /**
     * Convertit les settings n2p_* existants en 2 rangs integration_endpoints
     * (outbound + inbound). Les toggles métier (send_on_order_status_*, send_tasks,
     * priority_default) restent dans settings — ce n'est pas du transport.
     *
     * Idempotent : ne crée pas de doublon si les rangs existent déjà.
     * Le settings.n2p_api_token n'est PAS effacé — un refactor ultérieur des
     * lecteurs (orders-show.blade, etc.) le fera basculer sur l'endpoint.
     */
    public function up(): void
    {
        if (! Schema::hasTable('integration_endpoints') || ! Schema::hasTable('settings')) {
            return;
        }

        $baseUrl   = $this->readSetting('n2p_base_url', '');
        $apiToken  = $this->readSetting('n2p_api_token', '');
        $enabled   = filter_var($this->readSetting('n2p_enabled', ''), FILTER_VALIDATE_BOOLEAN);
        $verifySsl = filter_var($this->readSetting('n2p_verify_ssl', '1'), FILTER_VALIDATE_BOOLEAN);

        $this->upsertEndpoint([
            'name'                  => 'Nest2Prod — sortant (push jobs)',
            'system_code'           => 'n2p',
            'direction'             => 'outbound',
            'url'                   => $baseUrl !== '' ? $baseUrl : null,
            'auth_method'           => $apiToken !== '' ? 'bearer' : 'hmac',
            'bearer_token'          => $apiToken !== '' ? Crypt::encryptString($apiToken) : null,
            'hmac_secret'           => null,
            'hmac_header'           => 'X-N2P-Signature-256',
            'timestamp_header'      => 'X-N2P-Timestamp',
            'timestamp_tolerance_s' => 300,
            'verify_ssl'            => $verifySsl,
            'events'                => json_encode(['job.pushed']),
            'is_active'             => $enabled,
            'retry_max'             => 5,
            'retry_backoff'         => json_encode([60, 300, 900, 1800, 3600]),
        ]);

        $this->upsertEndpoint([
            'name'                  => 'Nest2Prod — entrant (retour prod)',
            'system_code'           => 'n2p',
            'direction'             => 'inbound',
            'url'                   => null,
            'auth_method'           => 'hmac',
            'bearer_token'          => null,
            'hmac_secret'           => Crypt::encryptString(Str::random(64)),
            'hmac_header'           => 'X-N2P-Signature-256',
            'timestamp_header'      => 'X-N2P-Timestamp',
            'timestamp_tolerance_s' => 300,
            'verify_ssl'            => true,
            'events'                => json_encode([]),
            'is_active'             => false,
            'retry_max'             => 5,
            'retry_backoff'         => json_encode([60, 300, 900, 1800, 3600]),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('integration_endpoints')) {
            return;
        }

        DB::table('integration_endpoints')
            ->where('system_code', 'n2p')
            ->whereIn('direction', ['inbound', 'outbound'])
            ->delete();
    }

    private function readSetting(string $key, string $default): string
    {
        $row = DB::table('settings')->where('key', $key)->first();
        if (! $row) {
            return $default;
        }

        $value = (string) ($row->value ?? '');
        if ($value === '') {
            return $default;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return $value;
        }
    }

    private function upsertEndpoint(array $attributes): void
    {
        $exists = DB::table('integration_endpoints')
            ->where('system_code', $attributes['system_code'])
            ->where('direction', $attributes['direction'])
            ->exists();

        if ($exists) {
            return;
        }

        $attributes['created_at'] = now();
        $attributes['updated_at'] = now();

        DB::table('integration_endpoints')->insert($attributes);
    }
};
