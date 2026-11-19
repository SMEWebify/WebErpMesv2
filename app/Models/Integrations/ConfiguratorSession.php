<?php

namespace App\Models\Integrations;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Session de configuration ouverte par l'ERP. Seule une session ouverte ici, encore
 * valide et pas encore fermée, peut accepter un résultat : un resultId venu du
 * navigateur n'est jamais cru seul.
 */
class ConfiguratorSession extends Model
{
    public const STATUS_PENDING = 'pending';
    /** Résultat relu, en attente de confirmation de l'utilisateur. */
    public const STATUS_PREVIEWED = 'previewed';
    public const STATUS_DONE = 'done';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'uuid',
        'external_configurator_id',
        'remote_session_id',
        'user_id',
        'document_type',
        'document_id',
        'line_id',
        'reference_externe',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'result'     => 'array',
        'expires_at' => 'datetime',
        'closed_at'  => 'datetime',
    ];

    protected $hidden = ['result'];

    public function configurator()
    {
        return $this->belongsTo(ExternalConfigurator::class, 'external_configurator_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PREVIEWED], true)
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
