<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountingAllocation extends Model
{
    use HasFactory;

    protected $fillable = ['ACCOUNT',  'LABEL',  'vat_id',  'VAT_ACCOUNT',  'CODE_ACCOUNT',  'TYPE_IMPUTATION'];
}
