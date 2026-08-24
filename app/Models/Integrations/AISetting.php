<?php

namespace App\Models\Integrations;

use Illuminate\Database\Eloquent\Model;

/**
 * Configuration IA (singleton).
 *
 * Accès normal via AISettingsResolver, qui gère fallback .env + cache.
 * Utiliser current() / instance() ici ne devrait servir qu'à l'écran d'admin.
 */
class AISetting extends Model
{
    // Sans ça, Laravel devine `a_i_settings` (chaque majuscule = un mot).
    protected $table = 'ai_settings';

    protected $fillable = [
        'provider',
        'api_key',
        'model',
        'max_tokens',
        'timeout_seconds',
        'base_url',
        'is_active',
    ];

    protected $casts = [
        'api_key'   => 'encrypted',
        'is_active' => 'boolean',
    ];

    /**
     * Instance active (la seule ligne utile). Retourne null si la table est vide.
     * Permet à l'UI de faire "si null → afficher un formulaire vierge".
     */
    public static function current(): ?self
    {
        return static::where('is_active', true)->first();
    }

    /**
     * Retourne la ligne existante ou en crée une par défaut (mode admin).
     */
    public static function instance(): self
    {
        return static::current() ?? static::create([
            'provider'    => 'claude',
            'max_tokens'  => 2048,
            'is_active'   => true,
        ]);
    }
}
