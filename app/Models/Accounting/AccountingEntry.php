<?php

namespace App\Models\Accounting;

use App\Models\Companies\Companies;
use Illuminate\Database\Eloquent\Model;
use App\Models\Accounting\AccountingAllocation;

class AccountingEntry extends Model
{
    protected $fillable = [
        'journal_code',
        'journal_label',
        'sequence_number',
        'accounting_date',
        'account_number',
        'account_label',
        'justification_reference',
        'justification_date',
        'auxiliary_account_number',
        'auxiliary_account_label',
        'document_reference',
        'document_date',
        'entry_label',
        'debit_amount',
        'credit_amount',
        'entry_lettering',
        'lettering_date',
        'validation_date',
        'currency_code',
        'invoice_line_id',
        'purchase_invoice_line_id',
    ];

    // Cast to specific data types
    protected $casts = [
        'accounting_date' => 'date',
        'justification_date' => 'date',
        'lettering_date' => 'date',
        'validation_date' => 'date',
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'currency_code' => 'decimal:2',
    ];

    // Example relationships (if necessary)
    public function company()
    {
        return $this->belongsTo(Companies::class);
    }

    public function accountingAllocation()
    {
        return $this->belongsTo(AccountingAllocation::class);
    }
}
