<?php

namespace App\Models\Workflow;

use Spatie\Activitylog\LogOptions;
use App\Models\Workflow\CreditNotes;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\InvoiceLines;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CreditNoteLines extends Model
{
    use HasFactory, LogsActivity;
    
    protected $fillable = [
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
