<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use App\Models\Accounting\AccountingVat;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AccountingAllocation extends Model
{
    use HasFactory;

    protected $fillable = ['account',  'label',  'accounting_vats_id',  'vat_account',  'code_account',  'type_imputation'];

    public function VAT()
    {
        return $this->belongsTo(AccountingVat::class, 'accounting_vats_id');
    }
}
