<?php

namespace App\Models\Integrations;

use App\Models\Companies\Companies;
use App\Models\Purchases\PurchaseInvoice;
use Illuminate\Database\Eloquent\Model;

/**
 * Boîte de réception des factures fournisseurs entrantes (Factur-X) reçues via
 * une PDP ou déposées manuellement. Sert d'étape de staging avant conversion en
 * facture d'achat (PurchaseInvoice) et rapprochement avec les réceptions.
 */
class PdpIncomingInvoice extends Model
{
    protected $table = 'pdp_incoming_invoices';

    // Statuts de traitement
    public const STATUS_RECEIVED          = 'received';           // reçue, fournisseur identifié
    public const STATUS_SUPPLIER_UNMATCHED = 'supplier_unmatched'; // fournisseur inconnu en base
    public const STATUS_CONVERTED         = 'converted';          // convertie en facture d'achat
    public const STATUS_REJECTED          = 'rejected';           // refusée

    protected $fillable = [
        'provider',
        'external_id',
        'supplier_company_id',
        'seller_name',
        'seller_vat',
        'seller_legal_id',
        'invoice_number',
        'issue_date',
        'due_date',
        'currency',
        'total_ht',
        'total_vat',
        'total_ttc',
        'buyer_reference',
        'status',
        'purchase_invoice_id',
        'payload',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date'   => 'date',
        'total_ht'   => 'decimal:2',
        'total_vat'  => 'decimal:2',
        'total_ttc'  => 'decimal:2',
        'payload'    => 'array',
    ];

    public function supplier()
    {
        return $this->belongsTo(Companies::class, 'supplier_company_id');
    }

    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }
}
