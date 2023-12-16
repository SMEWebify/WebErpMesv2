<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountingDelivery extends Model
{
    use HasFactory;

    protected $fillable = ['code',  'label',  'default'];
}
