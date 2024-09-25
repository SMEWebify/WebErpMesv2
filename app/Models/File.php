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
        'as_photo',
    ];

    public function companies()
    {
        return $this->morphedByMany(Companies::class, 'fileable');
    }

    public function opportunities()
    {
        return $this->morphedByMany(Opportunities::class, 'fileable');
    }

    public function quotes()
    {
        return $this->morphedByMany(Quotes::class, 'fileable');
    }

    public function orders()
    {
        return $this->morphedByMany(Orders::class, 'fileable');
    }

    public function deliverys()
    {
        return $this->morphedByMany(Deliverys::class, 'fileable');
    }

    public function invoices()
    {
        return $this->morphedByMany(Invoices::class, 'fileable');
    }

    public function products()
    {
        return $this->morphedByMany(Products::class, 'fileable');
    }

    public function purchaseReceipt()
    {
        return $this->morphedByMany(PurchaseReceipt::class, 'fileable');
    }

    public function stockMove()
    {
        return $this->morphedByMany(StockMove::class, 'fileable');
    }

    public function qualityNonConformity()
    {
        return $this->morphedByMany(QualityNonConformity::class, 'fileable');
    }
    
    public function UserManagement()
    {
        return $this->belongsTo(User::class, 'users_id');
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
