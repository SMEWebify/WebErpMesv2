<?php

namespace App\Models\Accounting;

use App\Traits\HasDefaultTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AccountingPaymentConditions extends Model
{
    use HasFactory; use HasDefaultTrait;

    protected $fillable = ['code',  'label',  'number_of_month',  'number_of_day',  'month_end',  'default'];
}
