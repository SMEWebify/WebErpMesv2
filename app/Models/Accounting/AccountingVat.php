<?php

namespace App\Models\Accounting;

use App\Models\Workflow\QuoteLines;
use Illuminate\Database\Eloquent\Model;
use App\Models\Accounting\AccountingAllocation;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AccountingVat extends Model
{
    use HasFactory;

    protected $fillable = ['code',  'label',  'rate',  'default'];


    public function VAT()
    {
        return $this->hasMany(AccountingAllocation::class);
    }

    public function QuoteLines()
    {
        return $this->hasMany(QuoteLines::class);
    }
}
