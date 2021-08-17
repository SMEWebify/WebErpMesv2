<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountingPaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = ['CODE',  'LABEL',  'CODE_ACCOUNT'];
}
