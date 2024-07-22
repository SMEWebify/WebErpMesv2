<?php

namespace App\Models\Workflow;

use Spatie\Activitylog\LogOptions;
use App\Models\Workflow\InvoiceLines;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Quality\QualityNonConformity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DeliveryLines extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['deliverys_id', 
                            'order_line_id', 
                            'ordre',
                            'qty',
                            'statu'
    ];

    public function delivery()
    {
        return $this->belongsTo(Deliverys::class, 'deliverys_id');
    }

    public function OrderLine()
    {
        return $this->belongsTo(OrderLines::class, 'order_line_id');
    }
    public function InvoiceLines()
    {
        return $this->belongsTo(InvoiceLines::class, 'delivery_line_id');
    }

    public function QualityNonConformity()
    {
        return $this->hasOne(QualityNonConformity::class, 'delivery_line_id');
    }

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['deliverys_id', 'statu']);
        // Chain fluent methods for configuration options
    }
}
