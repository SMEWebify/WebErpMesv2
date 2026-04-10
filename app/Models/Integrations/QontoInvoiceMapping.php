<?php

namespace App\Models\Integrations;

use App\Models\Workflow\Invoices;
use Illuminate\Database\Eloquent\Model;

class QontoInvoiceMapping extends Model
{
    protected $fillable = [
        'tenant_id',
        'invoice_id',
        'qonto_invoice_id',
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
