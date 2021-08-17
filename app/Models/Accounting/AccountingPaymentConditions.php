<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountingPaymentConditions extends Model
{
    use HasFactory;

    protected $fillable = ['CODE',  'LABEL',  'NUMBER_OF_MONTH',  'NUMBER_OF_DAY',  'MONTH_END'];
}
