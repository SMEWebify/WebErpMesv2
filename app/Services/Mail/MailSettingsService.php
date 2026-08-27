<?php

namespace App\Services\Mail;

use App\Models\Integrations\MailSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Applique la ligne `mail_settings` à la config runtime de Laravel.
 *
 * Ordre de résolution : DB → .env. Même schéma que AISettingsResolver.
 * Appelé au boot par MailSettingsServiceProvider, et à la volée dans
 * EmailController après une modification (via forget()).
 *
 * On cache 5 min : le boot HTTP frappe cette méthode systématiquement, on
 * n'a pas envie d'aller taper la table à chaque requête.
 */
class MailSettingsService
{
    private const CACHE_KEY = 'mail_settings.active';
    private const CACHE_TTL = 300;

    /**
     * @return array{
     *   source: 'db'|'env',
     *   driver: string,
     *   host: string|null,
     *   port: int|null,
     *   encryption: string|null,
     *   username: string|null,
     *   from_address: string,
     *   from_name: string,
     *   reply_to_use_user: bool,
     *   timeout: int,
     * }
     */
    public function resolved(): array
    {
        $db = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            try {
                if (! Schema::hasTable('mail_settings')) return null;
                $row = MailSetting::current();
                if (! $row) return null;

                return [
                    'driver'            => $row->driver ?: 'smtp',
                    'host'              => $row->host,
                    'port'              => $row->port ? (int) $row->port : null,
                    'encryption'        => $row->encryption ?: null,
                    'username'          => $row->username,
                    'password'          => $row->password,
                    'from_address'      => $row->from_address,
                    'from_name'         => $row->from_name,
                    'reply_to_use_user' => (bool) $row->reply_to_use_user,
                    'timeout'           => (int) ($row->timeout ?: 30),
                ];
            } catch (Throwable) {
                // Table absente (déploiement en cours) : fallback env.
                return null;
            }
        });

        if ($db && $db['host']) {
            return [
                'source'            => 'db',
                'driver'            => $db['driver'],
                'host'              => $db['host'],
                'port'              => $db['port'],
                'encryption'        => $db['encryption'],
                'username'          => $db['username'],
                'password'          => $db['password'],
                'from_address'      => $db['from_address'] ?: (string) config('mail.from.address'),
                'from_name'         => $db['from_name']    ?: (string) config('mail.from.name'),
                'reply_to_use_user' => $db['reply_to_use_user'],
                'timeout'           => $db['timeout'],
            ];
        }

        return [
            'source'            => 'env',
            'driver'            => (string) config('mail.default', 'smtp'),
            'host'              => (string) config('mail.mailers.smtp.host'),
            'port'              => (int) config('mail.mailers.smtp.port'),
            'encryption'        => config('mail.mailers.smtp.encryption'),
            'username'          => config('mail.mailers.smtp.username'),
            'password'          => config('mail.mailers.smtp.password'),
            'from_address'      => (string) config('mail.from.address'),
            'from_name'         => (string) config('mail.from.name'),
            'reply_to_use_user' => true,
            'timeout'           => 30,
        ];
    }

    /**
     * Pousse la config résolue dans `config('mail.*')` et réinitialise le
     * mailer résolu pour que le prochain `Mail::send()` réouvre la connexion
     * avec les bons paramètres.
     */
    public function apply(): void
    {
        $c = $this->resolved();

        Config::set('mail.default', $c['driver']);
        Config::set('mail.mailers.smtp.transport', 'smtp');
        Config::set('mail.mailers.smtp.host',       $c['host']);
        Config::set('mail.mailers.smtp.port',       $c['port']);
        Config::set('mail.mailers.smtp.encryption', $c['encryption']);
        Config::set('mail.mailers.smtp.username',   $c['username']);
        Config::set('mail.mailers.smtp.password',   $c['password']);
        Config::set('mail.mailers.smtp.timeout',    $c['timeout']);
        Config::set('mail.from.address',            $c['from_address']);
        Config::set('mail.from.name',               $c['from_name']);

        // Force la re-résolution du mailer par le MailManager au prochain envoi.
        Mail::purge('smtp');
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
