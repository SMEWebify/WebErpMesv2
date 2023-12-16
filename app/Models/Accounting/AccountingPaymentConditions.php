<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountingPaymentConditions extends Model
{
    use HasFactory;

    protected $fillable = ['code',  'label',  'number_of_month',  'number_of_day',  'month_end',  'default'];
}
