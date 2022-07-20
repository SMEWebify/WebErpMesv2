<?php

namespace App\Models\Workflow;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

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
