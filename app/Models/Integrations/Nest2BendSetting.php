<?php

namespace App\Models\Integrations;

use Illuminate\Database\Eloquent\Model;

/**
 * Liaison avec NEST2BEND (table singleton).
 *
 * Saisie dans l'écran Intégrations → NEST2BEND. La clé API est chiffrée en base, jamais
 * renvoyée au navigateur (affichée masquée) ni journalisée. Les colonnes d'état sont le
 * dernier résultat de GET /api/v1/compte (App\Services\Integrations\Nest2Bend\Nest2BendAccess).
 */
class Nest2BendSetting extends Model
{
    /** Aucune clé saisie : écran commercial. */
    public const STATUS_NONE = 'none';
    /** Clé saisie, jamais vérifiée (NEST2BEND injoignable à l'enregistrement). */
    public const STATUS_UNKNOWN = 'unknown';
    /** 401 : clé invalide ou révoquée. */
    public const STATUS_INVALID = 'invalid';
    /** 200 et droits.peut_ecrire = false. */
    public const STATUS_EXPIRED = 'expired';
    /** 200 et droits.peut_ecrire = true. */
    public const STATUS_ACTIVE = 'active';

    protected $table = 'nest2bend_settings';

    protected $fillable = [
        'base_url',
        'api_key',
        'status',
        'etat',
        'peut_ecrire',
        'echeance',
        'societe',
        'modules',
        'presses_max',
        'checked_at',
        'last_error',
    ];

    protected $casts = [
        // Chiffrée au niveau applicatif — jamais lue en clair côté DB.
        'api_key'     => 'encrypted',
        'peut_ecrire' => 'boolean',
        'echeance'    => 'datetime',
        'modules'     => 'array',
        'presses_max' => 'integer',
        'checked_at'  => 'datetime',
    ];

    protected $hidden = ['api_key'];

    public static function current(): ?self
    {
        return static::query()->latest('id')->first();
    }

    public function hasKey(): bool
    {
        return filled($this->getRawOriginal('api_key')) && filled($this->base_url);
    }

    /**
     * Clé affichée dans l'écran : préfixe et 4 derniers caractères seulement.
     */
    public function maskedKey(): ?string
    {
        if (! filled($this->getRawOriginal('api_key'))) {
            return null;
        }

        $key = (string) $this->api_key;
        $prefix = str_starts_with($key, 'n2b_') ? 'n2b_' : '';

        return $prefix . '••••••••' . (strlen($key) > 12 ? substr($key, -4) : '');
    }

    /**
     * Origine de NEST2BEND (schéma, hôte, port) : seule origine acceptée pour postMessage
     * et pour l'URL d'iframe renvoyée par embed-sessions.
     */
    public function origin(): ?string
    {
        return self::originOf($this->base_url);
    }

    public static function originOf(?string $url): ?string
    {
        $parts = parse_url((string) $url);
        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme']);
        $origin = $scheme . '://' . strtolower($parts['host']);
        $default = $scheme === 'https' ? 443 : 80;
        if (isset($parts['port']) && (int) $parts['port'] !== $default) {
            $origin .= ':' . $parts['port'];
        }

        return $origin;
    }
}
