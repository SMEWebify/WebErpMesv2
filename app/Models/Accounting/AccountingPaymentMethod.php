<?php

namespace App\Models\Accounting;

use App\Traits\HasDefaultTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AccountingPaymentMethod extends Model
{
    use HasFactory; use HasDefaultTrait;

    protected $fillable = ['code',  'label',  'code_account',  'default'];
}
