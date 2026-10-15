<?php

namespace App\Models\Workflow;

use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Une relance faite sur une facture : par e-mail (envoyé ou en échec), ou
 * tracée à la main après un appel ou un courrier.
 */
class InvoiceReminder extends Model
{
    public const CHANNEL_EMAIL  = 'email';
    public const CHANNEL_PHONE  = 'phone';
    public const CHANNEL_LETTER = 'letter';

    public const CHANNELS = [self::CHANNEL_EMAIL, self::CHANNEL_PHONE, self::CHANNEL_LETTER];

    protected $fillable = [
        'invoice_id',
        'reminder_level_id',
        'reminded_at',
        'channel',
        'user_id',
        'email_sent',
        'email_log_id',
        'amount_due',
        'days_late',
        'note',
    ];

    protected $casts = [
        'reminded_at' => 'datetime',
        'email_sent'  => 'boolean',
        'amount_due'  => 'decimal:2',
        'days_late'   => 'integer',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoices::class, 'invoice_id');
    }

    public function level()
    {
        return $this->belongsTo(InvoiceReminderLevel::class, 'reminder_level_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function emailLog()
    {
        return $this->belongsTo(EmailLog::class, 'email_log_id');
    }
}
