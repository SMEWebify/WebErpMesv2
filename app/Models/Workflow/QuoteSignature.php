<?php

namespace App\Models\Workflow;

use App\Models\File;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Une enveloppe de signature envoyée pour un devis.
 *
 * Le statut reprend le cycle de vie du prestataire. Il n'est jamais déduit de ce
 * que le navigateur du client raconte au retour de la signature : seul l'appel à
 * l'API du prestataire (QuoteSignatureService::sync) le fait avancer.
 */
class QuoteSignature extends Model
{
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_VOIDED = 'voided';

    public const PENDING = [self::STATUS_SENT, self::STATUS_DELIVERED];

    protected $fillable = [
        'quotes_id',
        'provider',
        'envelope_id',
        'signing_mode',
        'client_user_id',
        'status',
        'signer_name',
        'signer_email',
        'quote_total',
        'status_reason',
        'signed_file_id',
        'certificate_file_id',
        'sent_at',
        'completed_at',
        'last_checked_at',
    ];

    protected $casts = [
        'quote_total'     => 'decimal:2',
        'sent_at'         => 'datetime',
        'completed_at'    => 'datetime',
        'last_checked_at' => 'datetime',
    ];

    public function quote()
    {
        return $this->belongsTo(Quotes::class, 'quotes_id')->withoutGlobalScope(Quotes::TEMPLATE_SCOPE);
    }

    public function signedFile()
    {
        return $this->belongsTo(File::class, 'signed_file_id');
    }

    public function certificateFile()
    {
        return $this->belongsTo(File::class, 'certificate_file_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', self::PENDING);
    }

    public function isPending(): bool
    {
        return in_array($this->status, self::PENDING, true);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFinal(): bool
    {
        return ! $this->isPending();
    }
}
