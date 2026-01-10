<?php

namespace App\Models\Workflow;

use Illuminate\Support\Number;
use Spatie\Activitylog\LogOptions;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\CreditNotes;
use App\Models\Workflow\InvoiceLines;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CreditNoteLines extends Model
{
    use HasFactory, LogsActivity;
    
    // Fillable attributes for mass assignment
    protected $fillable= [
        'credit_note_id', 
        'order_line_id', 
        'invoice_line_id', 
        'product_id', 
        'qty', 
        'unit_price', 
    ];

    public function creditNote()
    {
        return $this->belongsTo(CreditNotes::class,'credit_note_id');
    }

    public function orderLine()
    {
        return $this->belongsTo(OrderLines::class, 'order_line_id');
    }

    public function invoiceLine()
    {
        return $this->belongsTo(InvoiceLines::class, 'invoice_line_id');
    }

    /**
     * Get the formatted selling price attribute.
     *
     * This method retrieves the selling price attribute, formats it as a currency
     * using the specified factory currency and application locale, and returns
     * the formatted value.
     *
     * @return string The formatted selling price.
     */
    public function getFormattedSellingPriceAttribute()
    {
        $factory = app('Factory'); 
        $currency = $factory->curency ?? 'EUR';
        return Number::currency($this->unit_price, $currency, config('app.locale'));
    }

    /**
     * Get the formatted creation date of the line.
     *
     * This accessor method returns the creation date of line
     * formatted as 'day month year' (e.g., '01 January 2023').
     *
     * @return string The formatted creation date.
     */
    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['invoices_id', 'invoice_status']);
        // Chain fluent methods for configuration options
    }
}
