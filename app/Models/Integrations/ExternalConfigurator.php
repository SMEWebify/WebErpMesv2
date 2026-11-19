<?php

namespace App\Models\Integrations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Configurateur externe branché sur l'ERP (Intégrations → Configurateurs).
 *
 * La clé API est chiffrée en base et jamais renvoyée au navigateur. Contrat :
 * docs/conception/configurateurs-externes.md.
 */
class ExternalConfigurator extends Model
{
    public const DISPLAY_IFRAME = 'iframe';
    public const DISPLAY_TAB = 'tab';

    /** Le prix du configurateur est brut : l'ERP applique la remise du client. */
    public const PRICE_GROSS = 'gross';
    /** Le prix du configurateur est le prix de vente : aucune remise. */
    public const PRICE_NET = 'net';

    public const DOCUMENT_TYPES = ['quote', 'order'];

    protected $fillable = [
        'code',
        'label',
        'base_url',
        'display',
        'document_types',
        'families',
        'price_mode',
        'active',
    ];

    protected $casts = [
        'api_key'        => 'encrypted',
        'document_types' => 'array',
        'families'       => 'array',
        'active'         => 'boolean',
        'last_check_at'  => 'datetime',
    ];

    protected $hidden = ['api_key'];

    /** @var array<int, string|null> libellés déjà lus (pastille des lignes) */
    private static array $labels = [];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true)->whereNotNull('api_key');
    }

    /**
     * Configurateurs proposés sur un type de document, et pour la famille de
     * l'article présélectionné s'il y en a une.
     */
    public static function availableFor(string $documentType, ?int $familyId = null)
    {
        if (! config('configurators.enabled')) {
            return collect();
        }

        return static::active()->orderBy('label')->get()->filter(function (self $c) use ($documentType, $familyId) {
            if (! in_array($documentType, $c->document_types ?: self::DOCUMENT_TYPES, true)) {
                return false;
            }

            return $familyId === null || empty($c->families) || in_array($familyId, array_map('intval', $c->families), true);
        })->values();
    }

    public static function labelFor(?int $id): ?string
    {
        if (! $id) {
            return null;
        }

        return self::$labels[$id] ??= static::whereKey($id)->value('label');
    }

    public function hasKey(): bool
    {
        return filled($this->api_key);
    }

    public function maskedKey(): ?string
    {
        if (! $this->hasKey()) {
            return null;
        }

        return '••••' . mb_substr($this->api_key, -4);
    }

    /** Origine (schéma, hôte, port) de l'API, seule acceptée pour l'iframe, les URL et les fichiers. */
    public function origin(): ?string
    {
        $parts = parse_url($this->base_url);
        if (empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        return $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
    }
}
