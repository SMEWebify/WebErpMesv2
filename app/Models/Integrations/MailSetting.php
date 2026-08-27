<?php

namespace App\Models\Integrations;

use Illuminate\Database\Eloquent\Model;

/**
 * Configuration SMTP par instance (table singleton).
 *
 * Utilisation runtime via App\Services\Mail\MailSettingsService, qui applique
 * la ligne active à `config('mail.*')` au boot. current() / instance() ne
 * servent qu'à l'écran d'admin.
 */
class MailSetting extends Model
{
    protected $table = 'mail_settings';

    protected $fillable = [
        'driver',
        'host',
        'port',
        'encryption',
        'username',
        'password',
        'from_address',
        'from_name',
        'reply_to_use_user',
        'timeout',
        'is_active',
    ];

    protected $casts = [
        // Chiffré au niveau applicatif — jamais lu en clair côté DB.
        'password'          => 'encrypted',
        'reply_to_use_user' => 'boolean',
        'is_active'         => 'boolean',
    ];

    protected $hidden = ['password'];

    public static function current(): ?self
    {
        return static::where('is_active', true)->first();
    }

    public static function instance(): self
    {
        return static::current() ?? static::create([
            'driver'            => 'smtp',
            'timeout'           => 30,
            'reply_to_use_user' => true,
            'is_active'         => true,
        ]);
    }
}
