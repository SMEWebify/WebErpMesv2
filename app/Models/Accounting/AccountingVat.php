<?php

namespace App\Models\Accounting;

use App\Traits\HasDefaultTrait;
use App\Models\Workflow\QuoteLines;
use Illuminate\Database\Eloquent\Model;
use App\Models\Accounting\AccountingAllocation;
use App\Models\Purchases\PurchaseLines;
use App\Models\Workflow\OrderLines;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AccountingVat extends Model
{
    use HasFactory; use HasDefaultTrait;

    protected $fillable = ['code',  'label',  'rate',  'default'];


    public function VAT()
    {
        return $this->hasMany(AccountingAllocation::class);
    }

    public function QuoteLines()
    {
        return $this->hasMany(QuoteLines::class);
    }

    public function OrderLines()
    {
        return $this->hasMany(OrderLines::class);
    }

    public function PurchaseLines()
    {
        return $this->hasMany(PurchaseLines::class);
    }
}
