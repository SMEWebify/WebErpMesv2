<?php

namespace App\Models;

use App\Models\User;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use App\Models\Products\Products;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\Deliverys;
use App\Models\Companies\Companies;
use App\Models\Products\StockMove;
use App\Models\Workflow\Opportunities;
use Illuminate\Database\Eloquent\Model;
use App\Models\Purchases\PurchaseReceipt;
use App\Models\Quality\QualityNonConformity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class File extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'name',
        'original_file_name',
        'type',
        'size',
        'companies_id',
        'opportunities_id',
        'quotes_id',
        'orders_id',
        'deliverys_id',
        'invoices_id',
        'products_id',
        'purchases_id',
        'purchase_receipts_id',
        'quality_non_conformities_id',
        'stock_move_id',
        'as_photo'
    ];
    

    public function companie()
    {
        return $this->belongsTo(Companies::class, 'companies_id');
    }

    public function quotes()
    {
        return $this->belongsTo(Quotes::class, 'quotes_id');
    }

    public function Opportunities()
    {
        return $this->belongsTo(Opportunities::class, 'opportunities_id');
    }

    public function order()
    {
        return $this->belongsTo(Orders::class, 'orders_id');
    }

    public function delivery()
    {
        return $this->belongsTo(Deliverys::class, 'deliverys_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoices::class, 'invoices_id');
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'products_id');
    }

    public function purchaseReceipt()
    {
        return $this->belongsTo(PurchaseReceipt::class, 'purchase_receipts_id');
    }

    public function qualityNonConformity()
    {
        return $this->belongsTo(QualityNonConformity::class, 'quality_non_conformities_id');
    }

    public function UserManagement()
    {
        return $this->belongsTo(User::class, 'users_id');
    }
    public function StockMove()
    {
        return $this->belongsTo(StockMove::class, 'stock_move_id');
    }

    public function GetPrettySize()
    {
        return round($this->size / 1000 ,2) .' Ko';
    }

    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }

}
