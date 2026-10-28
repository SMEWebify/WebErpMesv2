<?php

namespace App\Models\Quality;

use App\Models\File;
use App\Models\User;
use App\Models\Workflow\QuoteSignature;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Une enveloppe de signature envoyée au client pour une dérogation.
 *
 * Même cycle de vie que QuoteSignature (mêmes valeurs de statut, que le driver
 * DocuSign produit) ; seul DerogationSignatureService::sync le fait avancer.
 */
class QualityDerogationSignature extends Model
{
    public const STATUS_SENT = QuoteSignature::STATUS_SENT;
    public const STATUS_DELIVERED = QuoteSignature::STATUS_DELIVERED;
    public const STATUS_COMPLETED = QuoteSignature::STATUS_COMPLETED;
    public const STATUS_DECLINED = QuoteSignature::STATUS_DECLINED;
    public const STATUS_VOIDED = QuoteSignature::STATUS_VOIDED;

    public const PENDING = QuoteSignature::PENDING;

    protected $fillable = [
        'quality_derogation_id',
        'provider',
        'envelope_id',
        'status',
        'signer_name',
        'signer_email',
        'content_hash',
        'status_reason',
        'signed_file_id',
        'certificate_file_id',
        'sent_by',
        'sent_at',
        'completed_at',
        'last_checked_at',
    ];

    protected $casts = [
        'sent_at'         => 'datetime',
        'completed_at'    => 'datetime',
        'last_checked_at' => 'datetime',
    ];

    public function derogation()
    {
        return $this->belongsTo(QualityDerogation::class, 'quality_derogation_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
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
