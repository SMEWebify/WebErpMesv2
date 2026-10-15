<?php

namespace App\Models\Workflow;

use App\Models\Admin\EmailTemplate;
use Illuminate\Database\Eloquent\Model;

/**
 * Niveau de relance : proposé dès que le retard d'une facture atteint
 * delay_days jours après l'échéance (voir InvoiceReminderService).
 */
class InvoiceReminderLevel extends Model
{
    /** Couleurs Bootstrap proposées à la saisie (badge text-bg-*). */
    public const COLORS = ['info', 'primary', 'warning', 'orange', 'danger', 'secondary'];

    protected $fillable = ['level', 'label', 'delay_days', 'email_template_id', 'color'];

    protected $casts = [
        'level'      => 'integer',
        'delay_days' => 'integer',
    ];

    public function emailTemplate()
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }
}
