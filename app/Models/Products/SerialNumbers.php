<?php

namespace App\Models\Products;

use App\Models\Planning\Task;
use App\Models\Products\Products;
use App\Models\Companies\Companies;
use App\Models\Workflow\OrderLines;
use Illuminate\Database\Eloquent\Model;
use App\Models\Purchases\PurchaseReceiptLines;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SerialNumbers extends Model
{
    use HasFactory;

    protected $fillable = [
        'products_id',
        'companies_id',
        'order_line_id',
        'task_id',
        'purchase_receipt_line_id',
        'serial_number',
        'status',
        'additional_information',
    ];

    public function Product()
    {
        return $this->belongsTo(Products::class, 'products_id');
    }

    public function companie()
    {
        return $this->belongsTo(Companies::class, 'companies_id');
    }

    public function OrderLine()
    {
        return $this->belongsTo(OrderLines::class, 'order_line_id');
    }

    public function Task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function purchaseReceiptLines()
    {
        return $this->belongsTo(PurchaseReceiptLines::class, 'purchase_receipt_line_id');
    }

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
}
