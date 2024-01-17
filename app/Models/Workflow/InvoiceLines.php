<?php

namespace App\Models\Workflow;

use App\Models\Workflow\Invoices;
use Spatie\Activitylog\LogOptions;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\DeliveryLines;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceLines extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['invoices_id',
                            'order_line_id', 
                            'delivery_line_id',
                            'ordre',
                            'qty',
                            'accounting_allocation_id',
                            'invoice_status'
                        ];

    public function invoice()
    {
        return $this->belongsTo(Invoices::class, 'invoices_id');
    }

    public function orderLine()
    {
        return $this->belongsTo(OrderLines::class, 'order_line_id');
    }

    public function deliveryLine()
    {
        return $this->belongsTo(DeliveryLines::class, 'delivery_line_id');
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
