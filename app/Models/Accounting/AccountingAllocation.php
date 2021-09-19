<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use App\Models\Accounting\AccountingVat;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AccountingAllocation extends Model
{
    use HasFactory;

    protected $fillable = ['ACCOUNT',  'LABEL',  'accounting_vats_id',  'VAT_ACCOUNT',  'CODE_ACCOUNT',  'TYPE_IMPUTATION'];

    public function VAT()
    {
        return $this->belongsTo(AccountingVat::class, 'accounting_vats_id');
    }
}
