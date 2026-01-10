<?php

namespace App\Models\Accounting;

use Illuminate\Support\Number;
use App\Models\Companies\Companies;
use Illuminate\Database\Eloquent\Model;
use App\Models\Accounting\AccountingAllocation;
use App\Models\Assets\Asset;

/**
 * Class AccountingEntry
 *
 * This model represents an accounting entry in the system. It includes various attributes
 * related to the accounting entry such as journal code, sequence number, account details,
 * amounts, dates, and references. The model also defines relationships with other models
 * such as Companies and AccountingAllocation.
 *
 */
class AccountingEntry extends Model
{
    // Fillable attributes for mass assignment
    protected $fillable= [
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
        'asset_id',
        'exported'
    ];

    // Cast to specific data types
    protected $casts = [
        'accounting_date' => 'date',
        'justification_date' => 'date',
        'lettering_date' => 'date',
        'validation_date' => 'date',
        'document_date' => 'date',
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
    ];

    /**
     * Get the company that owns the accounting entry.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Companies::class);
    }

    /**
     * Get the accounting allocation that owns the accounting entry.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function accountingAllocation()
    {
        return $this->belongsTo(AccountingAllocation::class);
    }

    /**
     * Get the asset associated with the accounting entry.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Get the formatted debit amount attribute.
     *
     * This method retrieves the debit amount attribute, formats it as a currency
     * using the specified factory currency and application locale, and returns
     * the formatted value.
     *
     * @return string The formatted debit amount.
     */
    public function getFormattedDebitAmountAttribute()
    {
        $factory = app('Factory'); 
        $currency = $factory->curency ?? 'EUR';
        return Number::currency($this->debit_amount, $currency, config('app.locale'));
    }

    /**
     * Get the formatted credit amount attribute.
     *
     * This method retrieves the credit amount attribute, formats it as a currency
     * using the specified factory currency and application locale, and returns
     * the formatted value.
     *
     * @return string The formatted credit amount.
     */
    public function getFormattedCreditAmountAttribute()
    {
        $factory = app('Factory'); 
        $currency = $factory->curency ?? 'EUR';
        return Number::currency($this->credit_amount, $currency, config('app.locale'));
    }

}
