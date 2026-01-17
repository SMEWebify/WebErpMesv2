<?php

namespace App\Models\Purchases;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Purchases\PurchasesQuotation;

class PurchaseRfqGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'label',
        'description',
        'user_id',
    ];

    public function purchaseQuotations()
    {
        return $this->hasMany(PurchasesQuotation::class, 'rfq_group_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
