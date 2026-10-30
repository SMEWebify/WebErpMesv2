<?php

namespace App\Models\Workflow;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Avenant d'une commande client. Écrit seulement par App\Services\Workflow\OrderAmendments
 * (création, annulation) et App\Services\Workflow\ContractReview (visa) : rien en $fillable.
 */
class OrderAmendment extends Model
{
    use LogsActivity;

    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';
    public const CANCELLED = 'cancelled';

    protected $guarded = ['*'];

    protected $casts = [
        'amendment_date' => 'date',
        'reviewed_at' => 'datetime',
        'amount_before_ttc' => 'float',
        'amount_after_ttc' => 'float',
        'previous_review' => 'array',
    ];

    protected static $logName = 'order';

    public function order()
    {
        return $this->belongsTo(Orders::class, 'order_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['number', 'reason', 'requested_by', 'amendment_date', 'status', 'amount_before_ttc',
                'amount_after_ttc', 'review_decision', 'review_gaps', 'reviewed_by', 'reviewed_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
