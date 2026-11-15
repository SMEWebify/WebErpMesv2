<?php

namespace App\Models\Integrations;

use Illuminate\Database\Eloquent\Model;

/**
 * Liaison SharePoint (table singleton).
 *
 * Saisie dans l'écran Intégrations → SharePoint. Le secret de l'application Entra ID est
 * chiffré en base, jamais renvoyé au navigateur ni journalisé. `site_id` / `drive_id`
 * sont résolus par le test de connexion (App\Services\Integrations\SharePoint\GraphClient).
 */
class SharePointSetting extends Model
{
    /** Rien saisi, ou jamais testé. */
    public const STATUS_NONE = 'none';
    /** Site et bibliothèque trouvés au dernier test. */
    public const STATUS_OK = 'ok';
    /** Dernier test en échec (identifiants, droits, site introuvable). */
    public const STATUS_ERROR = 'error';

    public const DEFAULT_ROOT_FOLDER = 'ERP';

    protected $table = 'sharepoint_settings';

    protected $fillable = [
        'tenant_id',
        'client_id',
        'client_secret',
        'site_url',
        'library',
        'root_folder',
        'is_active',
        'site_id',
        'drive_id',
        'drive_web_url',
        'status',
        'checked_at',
        'last_error',
    ];

    protected $casts = [
        // Chiffré au niveau applicatif — jamais lu en clair côté DB.
        'client_secret' => 'encrypted',
        'is_active'     => 'boolean',
        'checked_at'    => 'datetime',
    ];

    protected $hidden = ['client_secret'];

    public static function current(): ?self
    {
        return static::query()->latest('id')->first();
    }

    /**
     * Réglage utilisable par la recopie : actif, complet et bibliothèque résolue.
     */
    public static function usable(): ?self
    {
        $setting = static::current();

        return $setting?->isUsable() ? $setting : null;
    }

    public function hasSecret(): bool
    {
        return filled($this->getRawOriginal('client_secret'));
    }

    public function isComplete(): bool
    {
        return filled($this->tenant_id)
            && filled($this->client_id)
            && $this->hasSecret()
            && filled($this->site_url);
    }

    public function isUsable(): bool
    {
        return $this->is_active && $this->isComplete() && filled($this->drive_id);
    }

    public function rootFolder(): string
    {
        return filled($this->root_folder) ? (string) $this->root_folder : self::DEFAULT_ROOT_FOLDER;
    }
}
