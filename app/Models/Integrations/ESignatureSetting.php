<?php

namespace App\Models\Integrations;

use Illuminate\Database\Eloquent\Model;

/**
 * Configuration de la signature électronique (table singleton).
 *
 * Saisie dans l'écran Intégrations → Signature électronique. Aucune valeur n'est
 * lue dans le .env : la clé privée et la clé HMAC sont chiffrées en base.
 */
class ESignatureSetting extends Model
{
    public const ENV_DEMO = 'demo';
    public const ENV_PRODUCTION = 'production';

    public const MODE_EMBEDDED = 'embedded';
    public const MODE_EMAIL = 'email';

    protected $table = 'esignature_settings';

    protected $fillable = [
        'provider',
        'environment',
        'integration_key',
        'api_user_id',
        'account_id',
        'private_key',
        'hmac_key',
        'signing_mode',
        'is_active',
    ];

    protected $casts = [
        // Chiffrés au niveau applicatif — jamais lus en clair côté DB.
        'private_key' => 'encrypted',
        'hmac_key'    => 'encrypted',
        'is_active'   => 'boolean',
    ];

    protected $hidden = ['private_key', 'hmac_key'];

    public static function current(): ?self
    {
        return static::query()->latest('id')->first();
    }

    /**
     * Tous les identifiants nécessaires à l'obtention d'un jeton sont saisis.
     */
    public function isComplete(): bool
    {
        return filled($this->integration_key)
            && filled($this->api_user_id)
            && filled($this->account_id)
            && filled($this->private_key);
    }

    public function isUsable(): bool
    {
        return $this->is_active && $this->isComplete();
    }

    public function isEmbedded(): bool
    {
        return $this->signing_mode !== self::MODE_EMAIL;
    }
}
