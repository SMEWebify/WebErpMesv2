<?php

namespace App\Models\Workflow;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Un report de date sur une ligne de commande : ancienne et nouvelle valeur
 * de delivery_date (date client) ou d'internal_delay (date interne).
 *
 * Écrit uniquement par OrderLinesObserver, jamais modifié ensuite.
 */
class OrderLineDelayHistory extends Model
{
    public const UPDATED_AT = null;

    public const FIELD_DELIVERY_DATE  = 'delivery_date';
    public const FIELD_INTERNAL_DELAY = 'internal_delay';
    public const FIELDS = [self::FIELD_DELIVERY_DATE, self::FIELD_INTERNAL_DELAY];

    /** Saisie sur l'écran des lignes de commande. */
    public const ORIGIN_MANUAL = 'manual';
    /** Date de la commande recopiée sur toutes ses lignes (fiche commande). */
    public const ORIGIN_ORDER = 'order';
    public const ORIGIN_IMPORT = 'import';
    public const ORIGIN_API = 'api';
    public const ORIGIN_PRE_ORDER = 'pre_order';
    /** Commande artisan, job, tâche planifiée. */
    public const ORIGIN_SYSTEM = 'system';

    protected $table = 'order_line_delay_histories';

    protected $fillable = [
        'order_lines_id',
        'field',
        'old_value',
        'new_value',
        'reason',
        'origin',
        'user_id',
    ];

    public function orderLine()
    {
        return $this->belongsTo(OrderLines::class, 'order_lines_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
