<?php

namespace App\Models\Integrations;

use App\Models\Workflow\Invoices;
use Illuminate\Database\Eloquent\Model;

/**
 * Suivi du dépôt d'une facture auprès d'une PDP (toutes plateformes confondues).
 * La colonne `provider` identifie le driver (ex: 'qonto').
 */
class PdpInvoiceSubmission extends Model
{
    protected $table = 'pdp_invoice_submissions';

    protected $fillable = [
        'tenant_id',
        'invoice_id',
        'provider',
        'external_id',
        'lifecycle_status',
        'rejection_reason',
        'submitted_at',
        'acknowledged_at',
    ];

    protected $casts = [
        'submitted_at'    => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoices::class, 'invoice_id');
    }
}
